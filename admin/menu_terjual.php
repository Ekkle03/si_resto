<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Ambil data history import
$sql_history = "SELECT * FROM menu_terjual ORDER BY tanggal_transaksi DESC, id_jual DESC";
$query_history = mysqli_query($koneksi, $sql_history);

// ── Navbar: siapkan variabel session ──────────────────────────────────────────
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
    <title>Menu Terjual</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Menu Terjual</h3>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <h4 class="card-title">Riwayat Penjualan Harian</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#modalImport">
                                        <i class="fa fa-file-import"></i> Import Penjualan Hari Ini
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
                                        <table id="table-history" class="table table-striped table-hover table-bordered">
                                            <thead class="bg-light">
                                                <tr class="text-center">
                                                    <th style="width: 50px;">NO</th>
                                                    <th>KODE</th>
                                                    <th>TGL TRANSAKSI</th>
                                                    <th>TOTAL MENU</th>
                                                    <th>WAKTU UPLOAD</th>
                                                    <th style="width: 120px;">ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no=1; while($h = mysqli_fetch_assoc($query_history)): ?>
                                                <tr class="text-center">
                                                    <td class="text-muted"><?= $no++ ?></td>
                                                    <td class="fw-bold text-dark"><?= $h['kode_transaksi'] ?></td>
                                                    <td><?= date('d/m/Y', strtotime($h['tanggal_transaksi'])) ?></td>
                                                    <td><span class="badge badge-info"><?= $h['total_item'] ?> Menu</span></td>
                                                    <td><?= $h['tanggal_upload'] ?></td>
                                                    <td class="text-center">
                                                        <div class="form-button-action justify-content-center">
                                                            <a href="detail_menu_terjual.php?id=<?= $h['id_jual'] ?>" class="btn btn-link btn-primary p-1" title="Lihat Detail">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-link btn-danger p-1" 
                                                                    onclick="confirmDelete(<?= $h['id_jual'] ?>)" title="Hapus Data">
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

    <div class="modal fade" id="modalConfirmDelete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold"> Konfirmasi</span>
                        <span class="fw-light"> Hapus </span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 24px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p>Apakah Anda yakin ingin menghapus riwayat penjualan ini?</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="btnDoDelete" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="proses_import_jual.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Import Laporan Penjualan (CSV)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Pilih Tanggal Penjualan</label>
                                <input type="date" name="tgl_trx" class="form-control" required value="<?= date('Y-m-d') ?>">
                                <small class="text-muted">Gunakan tanggal closing laporan tersebut.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Pilih File CSV</label>
                                <input type="file" name="file_csv" id="file_csv" class="form-control" accept=".csv" required>
                            </div>
                        </div>
                        
                        <div id="preview_area" style="display:none;">
                            <hr>
                            <label class="fw-bold mb-2 text-primary">Pratinjau Data Penjualan:</label>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd;">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-dark text-white sticky-top">
                                        <tr class="text-center">
                                            <th>MENU</th>
                                            <th>SKU</th>
                                            <th style="width: 100px;">QTY</th>
                                        </tr>
                                    </thead>
                                    <tbody id="isi_preview"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_import" id="btn_import" class="btn btn-primary" disabled>
                            <i class="fas fa-save me-1"></i> Proses & Potong Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  ← ini
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#table-history').DataTable({
            "order": []
        });

        if (window.location.search.indexOf('msg=') > -1) {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    window.history.replaceState({}, document.title, "menu_terjual.php");
                });
            }, 3000);
        }

        $('#file_csv').change(function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();

            reader.onload = function(event) {
                const csvData = event.target.result;
                const rows = csvData.split(/\r?\n|\r/);
                let validRows = [];

                if (rows.length < 2) {
                    alert("File CSV terlihat kosong atau format salah.");
                    return;
                }

                // MODE BACA PAKSA: Sisir semua baris
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].trim() === "") continue;

                    // Deteksi separator baris ini
                    let separator = rows[i].includes(';') ? ';' : ',';
                    
                    // Bersihkan tanda kutip bawaan Excel
                    let cols = rows[i].split(separator).map(c => c.trim().replace(/^"|"$/g, ''));

                    // Kita asumsikan Menu ada di Kolom 1 (Index 0). 
                    // Kita cari angka Qty di kolom manapun yang isinya angka.
                    let namaMenu = cols[0];
                    let qtyMenu = 0;

                    // Cek kolom dari index 1 sampai akhir untuk nyari angka QTY
                    for(let j = 1; j < cols.length; j++) {
                        let potentialQty = parseFloat(cols[j].replace(',', '.'));
                        if(!isNaN(potentialQty) && potentialQty > 0) {
                            qtyMenu = potentialQty;
                            break; // Ketemu angkanya, langsung stop pencarian di baris ini
                        }
                    }

                    // FILTER BLACKLIST: Abaikan baris yang bukan menu beneran
                    if (namaMenu !== undefined && namaMenu !== "") {
                        let lowerMenu = namaMenu.toLowerCase();
                        let isMetaData = lowerMenu.includes("periode") || 
                                         lowerMenu.includes("filter") || 
                                         lowerMenu.includes("cabang") || 
                                         lowerMenu.includes("laporan") || 
                                         lowerMenu.includes("total") ||
                                         lowerMenu.includes("menu") || // Hindari baris header "Nama Menu"
                                         lowerMenu.includes("item");

                        // Kalau lolos filter dan ada angkanya, sikat!
                        if (!isMetaData && qtyMenu > 0) {
                            validRows.push({
                                nama: namaMenu,
                                qty: qtyMenu
                            });
                        }
                    }
                }

                // Tampilkan hasil
                if (validRows.length > 0) {
                    $.post('get_kode_menu_preview.php', { data_menu: JSON.stringify(validRows) }, function(response) {
                        $('#isi_preview').html(response);
                        $('#preview_area').fadeIn();
                        $('#btn_import').prop('disabled', false);
                    }).fail(function() {
                        alert("Gagal memuat pratinjau. Pastikan file get_kode_menu_preview.php aman.");
                    });
                } else {
                    alert("Gagal membaca data.\nSistem tidak menemukan kombinasi Nama Menu dan Angka Qty yang valid di dalam file.");
                    $('#preview_area').hide();
                    $('#btn_import').prop('disabled', true);
                }
            };
            reader.readAsText(file);
        });
    });

    function confirmDelete(id) {
        $('#btnDoDelete').attr('href', 'delete_menu_jual.php?id=' + id);
        $('#modalConfirmDelete').modal('show');
    }
</script>
</body>
</html>