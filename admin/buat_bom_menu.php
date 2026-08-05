<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Ambil ID Menu dari URL
if (!isset($_GET['id'])) {
    header("Location: master_menu.php?msg=ID Menu tidak ditemukan");
    exit();
}

$id_induk = (int)$_GET['id'];
// REVISI: Samakan navigasi dinamis (biar kalau dari menu baliknya ke menu)
$from = isset($_GET['from']) ? $_GET['from'] : 'master_menu';

// 2. Ambil Nama Menu & Nama Satuan untuk Header (Dinamis untuk Yield)
$query_m = mysqli_query($koneksi, "SELECT m.nama_menu, s.nama_satuan 
    FROM master_menu m 
    JOIN master_satuan s ON m.id_satuan = s.id_satuan 
    WHERE m.id_menu = '$id_induk'");
$data_m = mysqli_fetch_assoc($query_m);

if (!$data_m) {
    header("Location: master_menu.php?msg=Menu tidak ditemukan");
    exit();
}

// --- LOGIKA TAMBAH KE KERANJANG (SESSION) ---
if (isset($_POST['tambah_keranjang'])) {
    $pilihan = explode('-', $_POST['pilihan_bahan']); 
    $tipe_item = $pilihan[0];
    $id_item = $pilihan[1];
    // REVISI: Pakai floatval agar angka murni & ambil ID Satuan dari dropdown
    $qty = floatval($_POST['qty']);
    $id_satuan_pilih = $_POST['id_satuan_pilih'];

    // Ambil nama bahan
    if ($tipe_item == 'BB') {
        $q = mysqli_query($koneksi, "SELECT nama_bb as nama FROM master_bahan_baku WHERE id_bb = '$id_item'");
    } else {
        $q = mysqli_query($koneksi, "SELECT nama_bsj as nama FROM master_bahan_setengah_jadi WHERE id_bsj = '$id_item'");
    }
    $res = mysqli_fetch_assoc($q);

    // REVISI: Ambil Nama Satuan berdasarkan pilihan dropdown
    $q_sat = mysqli_query($koneksi, "SELECT nama_satuan FROM master_satuan WHERE id_satuan = '$id_satuan_pilih'");
    $d_sat = mysqli_fetch_assoc($q_sat);

    // Simpan ke Session
    $_SESSION['keranjang_bom'][$id_induk][] = [
        'tipe_item'  => $tipe_item,
        'id_item'    => $id_item,
        'nama_bahan' => $res['nama'],
        'qty'        => $qty,
        'id_satuan'  => $id_satuan_pilih,
        'satuan'     => $d_sat['nama_satuan'] ?? '-'
    ];
    header("Location: buat_bom_menu.php?id=$id_induk&from=$from");
    exit();
}

// --- LOGIKA HAPUS DARI KERANJANG ---
if (isset($_GET['hapus_idx'])) {
    $idx = $_GET['hapus_idx'];
    unset($_SESSION['keranjang_bom'][$id_induk][$idx]);
    $_SESSION['keranjang_bom'][$id_induk] = array_values($_SESSION['keranjang_bom'][$id_induk]); 
    header("Location: buat_bom_menu.php?id=$id_induk&from=$from");
    exit();
}

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
    <title>Sistem Resto</title>
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
        .btn-outline-primary-thicker { 
            border-width: 2px !important; 
            font-weight: 500 !important; 
        }

        /* 1. Paksa Select2 agar lebarnya selalu 100% dan tidak 'flicker' */
        .select2-container {
            width: 100% !important;
            display: block !important;
        }

        /* 2. Menyesuaikan tinggi dan border agar senada dengan input KaiAdmin */
        .select2-container--default .select2-selection--single {
            height: 40px !important;
            border: 1px solid #ebedf2 !important;
            padding-top: 5px;
            border-radius: 5px !important;
        }

        /* 3. Menyesuaikan posisi panah dropdown agar pas di tengah */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
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
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Penyusunan Resep Menu: <b><?= htmlspecialchars($data_m['nama_menu']) ?></b></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row align-items-end">
                            <input type="hidden" name="from_origin" value="<?= $from ?>">
                            <div class="col-md-5 mb-3">
                                <label class="fw-bold">Pilih Bahan Baku / BSJ</label>
                                <select name="pilihan_bahan" id="pilihan_bahan" class="form-select select2" required>
                                    <option value="">-- Cari Bahan --</option>
                                    <optgroup label="BAHAN BAKU (BB)">
                                        <?php
                                        $bb = mysqli_query($koneksi, "SELECT id_bb, nama_bb FROM master_bahan_baku ORDER BY nama_bb ASC");
                                        while($r = mysqli_fetch_assoc($bb)) echo "<option value='BB-{$r['id_bb']}'>[BB] {$r['nama_bb']}</option>";
                                        ?>
                                    </optgroup>
                                    <optgroup label="BAHAN ½ JADI (BSJ)">
                                        <?php
                                        $bsj = mysqli_query($koneksi, "SELECT id_bsj, nama_bsj FROM master_bahan_setengah_jadi ORDER BY nama_bsj ASC");
                                        while($r = mysqli_fetch_assoc($bsj)) echo "<option value='BSJ-{$r['id_bsj']}'>[BSJ] {$r['nama_bsj']}</option>";
                                        ?>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="fw-bold">Qty</label>
                                <input type="number" step="0.001" name="qty" class="form-control" placeholder="0.000" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="fw-bold">Satuan</label>
                                <input type="hidden" name="id_satuan_pilih" id="id_satuan_hidden">
                                <input type="text" id="nama_satuan_tampil" class="form-control bg-light" placeholder="Satuan" readonly>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="submit" name="tambah_keranjang" class="btn btn-success w-100">
                                    <i class="fa fa-plus"></i> Tambah
                                </button>
                            </div>
                        </form>

                        <hr>

                        <form action="simpan_bom.php" method="POST">
                            <input type="hidden" name="id_induk" value="<?= $id_induk ?>">
                            <input type="hidden" name="tipe_bom" value="MENU">
                            <input type="hidden" name="source_from" value="<?= $from ?>">
                            <input type="hidden" name="target_hasil" value="1">

                            <h5 class="fw-bold mb-3 mt-4">Daftar Komposisi Resep</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th width="50">No</th>
                                            <th>Nama Bahan</th>
                                            <th width="120">Qty</th>
                                            <th width="150">Satuan</th>
                                            <th width="100">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        if (!empty($_SESSION['keranjang_bom'][$id_induk])) {
                                            foreach ($_SESSION['keranjang_bom'][$id_induk] as $idx => $item) {
                                                ?>
                                                <tr>
                                                    <td class='text-center'><?= $no ?></td>
                                                    <td class='text-start'><?= htmlspecialchars($item['nama_bahan']) ?></td>
                                                    <td class='text-center fw-bold'><?= $item['qty'] ?></td>
                                                    <td class='text-center'><?= htmlspecialchars($item['satuan']) ?></td>
                                                    <td class='text-center'>
                                                        <a href='buat_bom_menu.php?id=<?= $id_induk ?>&hapus_idx=<?= $idx ?>&from=<?= $from ?>' class='btn btn-danger btn-sm'>
                                                            <i class='fa fa-trash'></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                                $no++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted'>Belum ada bahan yang ditambahkan.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="master_menu.php" class="btn btn-secondary px-4">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>

                                <?php if (!empty($_SESSION['keranjang_bom'][$id_induk])): ?>
                                    <button type="submit" name="simpan_permanen" class="btn btn-primary px-4 shadow">
                                        <i class="fa fa-save"></i> SIMPAN RESEP MENU
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Gunakan inisialisasi paling stabil
    $('#pilihan_bahan').select2({
        placeholder: "-- Cari Bahan --",
        width: '100%',
        allowClear: true
    });

    // Pasang event change
    $('#pilihan_bahan').on('select2:select', function (e) {
        var p = $(this).val();
        if (p != "") {
            $.post('get_satuan_ajax.php', { pilihan: p, mode: 'terkecil' }, function(res) {
                try {
                    var data = JSON.parse(res);
                    $('#id_satuan_hidden').val(data.id);
                    $('#nama_satuan_tampil').val(data.nama);
                } catch (e) {
                    console.error("Gagal parse JSON:", res);
                }
            });
        }
    });

    // Reset satuan jika pilihan dihapus
    $('#pilihan_bahan').on('select2:unselect', function (e) {
        $('#id_satuan_hidden').val('');
        $('#nama_satuan_tampil').val('');
    });
});
</script>
</body>
</html>