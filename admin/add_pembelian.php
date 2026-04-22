<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. GENERATE KODE PB UNIK (Reset per Bulan, Lanjut Terus Beda Tanggal)
$bulan_tahun = date('my'); // Format: 0426 (Bulan 04, Tahun 26)
$tgl_full    = date('dmy'); // Format: 170426 (Tgl 17, Bulan 04, Tahun 26)

// Query mencari urutan tertinggi di bulan dan tahun yang sama (mengabaikan 2 digit tanggal)
$sql_kode = "SELECT MAX(CAST(RIGHT(kode_pembelian, 2) AS UNSIGNED)) as max_urut 
             FROM pembelian 
             WHERE kode_pembelian LIKE 'PB__{$bulan_tahun}%'";

$query_kode = mysqli_query($koneksi, $sql_kode);
$data_kode  = mysqli_fetch_assoc($query_kode);

// Ambil nomor urut terakhir, kalau kosong berarti 0
$noUrut = $data_kode['max_urut'] ?? 0;
$noUrut++; // Tambah 1 untuk nota baru

// Gabungkan PB, tanggal hari ini, dan urutan bulanan (format 2 digit)
$kode_otomatis = "PB" . $tgl_full . sprintf("%02s", $noUrut);

// 2. AMBIL DATA BAHAN BAKU (Satuannya otomatis deteksi Satuan Besar)
$sql_bahan = "SELECT 
                b.id_bb, b.kode_bb, b.nama_bb, 
                s_kecil.nama_satuan AS satuan_default,
                s_besar.nama_satuan AS satuan_konversi
              FROM master_bahan_baku b 
              JOIN master_satuan s_kecil ON b.id_satuan = s_kecil.id_satuan
              LEFT JOIN master_konversi mk ON b.id_bb = mk.id_komponen AND mk.tipe_bahan = 'BB'
              LEFT JOIN master_satuan s_besar ON mk.satuan_besar = s_besar.id_satuan
              ORDER BY b.nama_bb ASC";

$bahan_baku = mysqli_query($koneksi, $sql_bahan);
$opsi_bahan = "";

while($row = mysqli_fetch_assoc($bahan_baku)) {
    $satuan_tampil = !empty($row['satuan_konversi']) ? $row['satuan_konversi'] : $row['satuan_default'];
    // Gunakan htmlspecialchars agar atribut HTML aman
    $opsi_bahan .= "<option value='".$row['id_bb']."' data-satuan='".htmlspecialchars($satuan_tampil, ENT_QUOTES)."'>".$row['kode_bb']." - ".$row['nama_bb']."</option>";
}

// Variabel Navbar
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
    <title>Pembelian</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .table-detail thead th { background: #f8f9fa !important; color: #495057 !important; font-weight: 700 !important; text-transform: uppercase; font-size: 12px; border-bottom: 2px solid #dee2e6 !important; }
        .span-satuan { font-weight: 600; color: #2a2b2d; }
        .select2-container--default .select2-selection--single { height: 38px !important; border: 1px solid #ebedf2 !important; padding-top: 4px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px !important; font-size: 14px !important; color: #495057 !important; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="dashboard.php" class="logo"><img src="assets/img/logo/logo_resto.png" alt="Logo" class="navbar-brand" height="30" /></a>
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
                                    <div class="avatar-sm"><img src="<?= $foto ?>" class="avatar-img rounded-circle" onerror="this.src='assets/img/profil/default.png'"/></div>
                                    <span class="profile-username"><span class="op-7">Selamat Datang,</span> <span class="fw-bold"><?= $nama ?></span></span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg"><img src="<?= $foto ?>" class="avatar-img rounded" onerror="this.src='assets/img/profil/default.png'"/></div>
                                                <div class="u-text"><h4><?= $nama ?></h4><p class="text-muted">@<?= $username ?></p><a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a></div>
                                            </div>
                                        </li>
                                        <li><div class="dropdown-divider"></div><a class="dropdown-item" href="../logout.php">Logout</a></li>
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
                        <div>
                            <h3 class="fw-bold mb-1">Buat Rencana Belanja</h3>
                            <span class="text-muted">Nota: <b><?= $kode_otomatis ?></b> | Tgl: <?= date('d/m/Y') ?></span>
                        </div>
                        <div class="d-flex" style="gap: 8px;">
                            <button type="submit" name="btn_simpan" form="formPembelian" class="btn btn-success fw-bold px-4">SIMPAN</button>
                            <a href="pembelian.php" class="btn btn-white border fw-bold text-danger">BATAL</a>
                        </div>
                    </div>

                    <form id="formPembelian" action="proses_add_pembelian.php" method="POST">
                        <div class="card card-round shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="fw-bold text-muted small">KODE PEMBELIAN</label>
                                        <input type="text" name="kode_pembelian" class="form-control fw-bold bg-light text-primary" value="<?= $kode_otomatis ?>" readonly>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="fw-bold text-muted small">TANGGAL RENCANA</label>
                                        <input type="date" name="tgl_pembelian" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="fw-bold text-muted small">KETERANGAN (OPSIONAL)</label>
                                        <textarea name="keterangan" class="form-control" rows="1" placeholder="Misal: Stok Minggu ke-2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header bg-white d-flex align-items-center">
                                <div class="card-title fw-bold">Daftar Bahan Belanja</div>
                                <button type="button" class="btn btn-primary btn-round btn-sm ms-auto fw-bold" id="btn-tambah-baris">
                                    <i class="fa fa-plus me-1"></i> Tambah Item
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="overflow-x: visible;"> 
                                    <table class="table table-hover table-bordered table-detail mb-0" id="table-detail">
                                        <thead class="text-center">
                                            <tr>
                                                <th class="text-start" style="padding-left: 20px !important;">NAMA BAHAN BAKU</th>
                                                <th style="width: 150px;">QTY</th>
                                                <th style="width: 150px;">SATUAN</th>
                                                <th style="width: 70px;">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody id="isi-detail">
                                            <tr>
                                                <td>
                                                    <select name="id_bb[]" class="form-control select2-bahan" required>
                                                        <option value="">-- Pilih Bahan --</option>
                                                        <?= $opsi_bahan ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" name="qty_beli[]" class="form-control text-center fw-bold text-primary" step="any" placeholder="0" required></td>
                                                <td class="text-center align-middle"><span class="span-satuan">-</span></td>
                                                <td class="text-center align-middle">
                                                    <button type="button" class="btn btn-link btn-danger p-0 btn-hapus-baris"><i class="fa fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            function initSelect2() {
                $('.select2-bahan').select2({ theme: "default", width: '100%', placeholder: "-- Cari Nama Bahan --" });
            }
            initSelect2();

            // Fungsi Clone Tambah Baris
            $('#btn-tambah-baris').click(function() {
                $('.select2-bahan').select2('destroy');
                var barisBaru = `<tr>
                    <td><select name="id_bb[]" class="form-control select2-bahan" required><option value="">-- Pilih Bahan --</option><?= $opsi_bahan ?></select></td>
                    <td><input type="number" name="qty_beli[]" class="form-control text-center fw-bold text-primary" step="any" placeholder="0" required></td>
                    <td class="text-center align-middle"><span class="span-satuan">-</span></td>
                    <td class="text-center align-middle"><button type="button" class="btn btn-link btn-danger p-0 btn-hapus-baris"><i class="fa fa-trash"></i></button></td>
                </tr>`;
                $('#isi-detail').append(barisBaru);
                initSelect2();
            });

            // Fungsi Hapus Baris
            $(document).on('click', '.btn-hapus-baris', function() {
                if($('#isi-detail tr').length > 1) { 
                    $(this).closest('tr').remove(); 
                } else { 
                    Swal.fire({ icon: 'warning', title: 'Minimal 1 Item', text: 'Rencana belanja tidak boleh kosong!' }); 
                }
            });

            // Fungsi Cek Duplikat dan Update Satuan
            $(document).on('change', '.select2-bahan', function (e) {
                var currentSelect = $(this);
                var selectedValue = currentSelect.val();
                var isDuplicate = false;

                // Cek apakah bahan udah dipilih di baris lain
                if (selectedValue !== "") {
                    $('.select2-bahan').not(currentSelect).each(function() {
                        if ($(this).val() === selectedValue) {
                            isDuplicate = true;
                            return false; // Berhenti looping kalau ketemu kembarannya
                        }
                    });
                }

                // Kalau duplikat, munculkan error dan reset inputnya
                if (isDuplicate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops, Bahan Dobel!',
                        text: 'Bahan ini sudah kamu tambahkan di daftar. Silakan ubah QTY di baris yang sudah ada.',
                        confirmButtonColor: '#d33'
                    });
                    
                    // Reset value select2 ke kosong
                    currentSelect.val('').trigger('change.select2');
                    currentSelect.closest('tr').find('.span-satuan').text('-');
                } else {
                    // Kalau aman (nggak duplikat), tarik nama satuannya
                    var satuan = currentSelect.find(':selected').data('satuan');
                    currentSelect.closest('tr').find('.span-satuan').text(satuan || '-');
                }
            });
        });
    </script>
</body>
</html>