<?php
// Mulai session di awal
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Ambil data item (join ke satuan & kategori untuk tampilan)
$sql = "SELECT i.*, s.nama_satuan, k.nama_kategori
        FROM master_item i
        JOIN master_satuan s ON i.id_satuan = s.id_satuan
        JOIN master_kategori k ON i.id_kategori = k.id_kategori
        ORDER BY i.id_item ASC";
$result = mysqli_query($koneksi, $sql);

// Ambil data untuk dropdown (satuan & kategori)
$satuan_rs   = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
$kategori_rs = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori ORDER BY nama_kategori ASC");

// Helper format rupiah (Indonesia): 1.234,56
function rupiah($angka) {
    return number_format((float)$angka, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Item - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"],
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
        .text-end { text-align: end !important; }
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
                                <h4 class="card-title">Data Master Item</h4>
                                <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addItemModal">
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
                                    <table id="basic-datatables" class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th style="width: 10%;" class="text-center">ID Item</th>
                                            <th>Nama Item</th>
                                            <th style="width: 10%;" class="text-center">Jenis</th>
                                            <th style="width: 15%;" class="text-end">Harga Beli</th>
                                            <th style="width: 15%;">Satuan</th>
                                            <th style="width: 20%;">Kategori</th>
                                            <th style="width: 18%;" class="text-center">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars($row['id_item']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_item']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['jenis_item']) ?></td>
                                                <td class="text-end"><?= rupiah($row['harga_beli']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                <td class="text-center">
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-primary btn-sm btn-update" data-bs-toggle="tooltip" title="Edit Data"
                                                            data-id_item="<?= htmlspecialchars($row['id_item']) ?>"
                                                            data-nama_item="<?= htmlspecialchars($row['nama_item']) ?>"
                                                            data-jenis_item="<?= htmlspecialchars($row['jenis_item']) ?>"
                                                            data-harga_beli="<?= htmlspecialchars($row['harga_beli']) ?>"
                                                            data-id_satuan="<?= htmlspecialchars($row['id_satuan']) ?>"
                                                            data-id_kategori="<?= htmlspecialchars($row['id_kategori']) ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-bs-toggle="tooltip" title="Hapus Data"
                                                            data-id_item="<?= htmlspecialchars($row['id_item']) ?>">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div><!-- table-responsive -->
                            </div>
                        </div><!-- card -->
                    </div>
                </div><!-- row -->
            </div>
        </div>
    </div>
</div>

<!-- ================== Modals ================== -->

<!-- Tambah Item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_item.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addItemModalLabel">Tambah Data Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-7">
            <label class="form-label" for="nama_item">Nama Item</label>
            <input type="text" class="form-control" id="nama_item" name="nama_item" placeholder="Masukkan nama item" required />
          </div>
          <div class="col-md-5">
            <label class="form-label" for="jenis_item">Jenis Item</label>
            <select class="form-select" id="jenis_item" name="jenis_item" required>
              <option value="" disabled selected>-- Pilih Jenis --</option>
              <option value="RAW">RAW</option>
              <option value="PREP">PREP</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="harga_beli">Harga Beli</label>
            <input type="number" class="form-control" id="harga_beli" name="harga_beli" step="0.01" min="0" placeholder="0.00" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="id_satuan">Satuan</label>
            <select class="form-select" id="id_satuan" name="id_satuan" required>
              <option value="" disabled selected>-- Pilih Satuan --</option>
              <?php
              mysqli_data_seek($satuan_rs, 0);
              while ($s = mysqli_fetch_assoc($satuan_rs)): ?>
                <option value="<?= htmlspecialchars($s['id_satuan']) ?>">
                    <?= htmlspecialchars($s['nama_satuan']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="id_kategori">Kategori</label>
            <select class="form-select" id="id_kategori" name="id_kategori" required>
              <option value="" disabled selected>-- Pilih Kategori --</option>
              <?php
              mysqli_data_seek($kategori_rs, 0);
              while ($k = mysqli_fetch_assoc($kategori_rs)): ?>
                <option value="<?= htmlspecialchars($k['id_kategori']) ?>">
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
              <?php endwhile; ?>
            </select>
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

<!-- Update Item -->
<div class="modal fade" id="updateItemModal" tabindex="-1" aria-labelledby="updateItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="update_item.php">
        <input type="hidden" name="id_item" id="update_id_item" />
        <div class="modal-header">
          <h5 class="modal-title" id="updateItemModalLabel">Update Data Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-7">
            <label class="form-label" for="update_nama_item">Nama Item</label>
            <input type="text" class="form-control" id="update_nama_item" name="nama_item" placeholder="Ubah nama item" required />
          </div>
          <div class="col-md-5">
            <label class="form-label" for="update_jenis_item">Jenis Item</label>
            <select class="form-select" id="update_jenis_item" name="jenis_item" required>
              <option value="RAW">RAW</option>
              <option value="PREP">PREP</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="update_harga_beli">Harga Beli</label>
            <input type="number" class="form-control" id="update_harga_beli" name="harga_beli" step="0.01" min="0" placeholder="0.00" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="update_id_satuan">Satuan</label>
            <select class="form-select" id="update_id_satuan" name="id_satuan" required>
              <?php
              mysqli_data_seek($satuan_rs, 0);
              while ($s = mysqli_fetch_assoc($satuan_rs)): ?>
                <option value="<?= htmlspecialchars($s['id_satuan']) ?>">
                    <?= htmlspecialchars($s['nama_satuan']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="update_id_kategori">Kategori</label>
            <select class="form-select" id="update_id_kategori" name="id_kategori" required>
              <?php
              mysqli_data_seek($kategori_rs, 0);
              while ($k = mysqli_fetch_assoc($kategori_rs)): ?>
                <option value="<?= htmlspecialchars($k['id_kategori']) ?>">
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
              <?php endwhile; ?>
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

<!-- Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus item ini?</p>
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
$(document).ready(function() {
    $('#basic-datatables').DataTable({
        columnDefs: [
            { targets: [3], className: 'text-end' }
        ]
    });

    // Auto-hide alert + redirect
    if ($('.alert-success').length) {
        setTimeout(function() {
            $('.alert-success').fadeOut('slow', function() {
                window.location.href = 'master_item.php';
            });
        }, 3000);
    }
});

// Update modal populate
document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', function() {
        const id_item    = this.dataset.id_item;
        const nama_item  = this.dataset.nama_item;
        const jenis_item = this.dataset.jenis_item;
        const harga_beli = this.dataset.harga_beli;
        const id_satuan  = this.dataset.id_satuan;
        const id_kategori= this.dataset.id_kategori;

        document.getElementById('update_id_item').value = id_item;
        document.getElementById('update_nama_item').value = nama_item;
        document.getElementById('update_jenis_item').value = jenis_item;
        document.getElementById('update_harga_beli').value = parseFloat(harga_beli).toFixed(2);
        document.getElementById('update_id_satuan').value = id_satuan;
        document.getElementById('update_id_kategori').value = id_kategori;

        new bootstrap.Modal(document.getElementById('updateItemModal')).show();
    });
});

// Delete modal
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id_item;
        document.getElementById('confirmDeleteLink').href = 'delete_item.php?id=' + id;
        new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
    });
});
</script>
</body>
</html>
