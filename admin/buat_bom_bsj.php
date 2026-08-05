<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// REVISI 1: Ambil parameter 'from' dari URL untuk navigasi pintar
$from = isset($_GET['from']) ? $_GET['from'] : 'master_bsj';

// Pastikan ada ID yang dikirim
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_induk = (int) $_GET['id'];

// --- LOGIKA KERANJANG (BAGIAN ATAS) ---
if (isset($_POST['tambah_keranjang'])) {
    $pilihan = explode('-', $_POST['pilihan_bahan']);
    $tipe_item = $pilihan[0]; 
    $id_item = $pilihan[1];
    $qty = floatval($_POST['qty']); 
    $id_satuan_dipilih = $_POST['id_satuan_pilih']; // Menangkap ID Satuan dari dropdown

    // 1. Ambil Nama Bahan
    if ($tipe_item == 'BB') {
        $q = mysqli_query($koneksi, "SELECT nama_bb as nama FROM master_bahan_baku WHERE id_bb = '$id_item'");
    } else {
        $q = mysqli_query($koneksi, "SELECT nama_bsj as nama FROM master_bahan_setengah_jadi WHERE id_bsj = '$id_item'");
    }
    $res = mysqli_fetch_assoc($q);

    // 2. Ambil Nama Satuan berdasarkan ID yang dipilih user di dropdown
    $q_sat = mysqli_query($koneksi, "SELECT nama_satuan FROM master_satuan WHERE id_satuan = '$id_satuan_dipilih'");
    $d_sat = mysqli_fetch_assoc($q_sat);

    // Simpan ke Session
    $_SESSION['keranjang_bom'][$id_induk][] = [
        'tipe_item'  => $tipe_item,
        'id_item'    => $id_item,
        'nama_bahan' => $res['nama'],
        'qty'        => $qty, 
        'id_satuan'  => $id_satuan_dipilih, // ID yang dipilih user
        'satuan'     => $d_sat['nama_satuan'] // Nama satuan untuk tampilan tabel
    ];
    
    $f_back = isset($_POST['from_origin']) ? $_POST['from_origin'] : 'master_bsj';
    header("Location: buat_bom_bsj.php?id=$id_induk&from=$f_back");
    exit();
}

// Logika Hapus Item
if (isset($_GET['hapus_idx'])) {
    $idx = $_GET['hapus_idx'];
    $f = $_GET['from'];
    unset($_SESSION['keranjang_bom'][$id_induk][$idx]);
    $_SESSION['keranjang_bom'][$id_induk] = array_values($_SESSION['keranjang_bom'][$id_induk]); 
    header("Location: buat_bom_bsj.php?id=$id_induk&from=$f");
    exit();
}

// Ambil Data BSJ Induk + Nama Satuannya
$query_header = "SELECT b.*, s.nama_satuan 
                 FROM master_bahan_setengah_jadi b 
                 JOIN master_satuan s ON b.id_satuan = s.id_satuan 
                 WHERE b.id_bsj = '$id_induk'";
$data_bsj = mysqli_fetch_assoc(mysqli_query($koneksi, $query_header));

// Tentukan link kembali berdasarkan parameter 'from'
if ($from == 'master_menu') {
    $btn_kembali = "master_menu.php";
} elseif ($from == 'master_bom') {
    $btn_kembali = "master_bom.php";
} else {
    $btn_kembali = "master_bahan_setengahjadi.php";
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
        .btn-outline-primary-thicker { border-width: 2px !important; font-weight: 500 !important; }
        .select2-container {
            display: block !important;
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 40px !important;
            border: 1px solid #ebedf2 !important;
            padding-top: 5px;
            border-radius: 5px !important;
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
                        <h4 class="card-title">Buat Resep BSJ: <b><?= htmlspecialchars($data_bsj['nama_bsj']) ?></b></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row align-items-end">
                            <input type="hidden" name="from_origin" value="<?= $from ?>">
                            <div class="col-md-5 mb-3">
                                <label class="fw-bold">Pilih Bahan</label>
                                <select name="pilihan_bahan" id="pilihan_bahan" class="form-select select2" required>
                                    <option value="">-- Pilih Bahan --</option>
                                    <optgroup label="Bahan Baku">
                                        <?php
                                        $bb = mysqli_query($koneksi, "SELECT id_bb, nama_bb FROM master_bahan_baku ORDER BY nama_bb ASC");
                                        while($r = mysqli_fetch_assoc($bb)) echo "<option value='BB-{$r['id_bb']}'>[BB] {$r['nama_bb']}</option>";
                                        ?>
                                    </optgroup>
                                    <optgroup label="Bahan Setengah Jadi">
                                        <?php
                                        $bsj = mysqli_query($koneksi, "SELECT id_bsj, nama_bsj FROM master_bahan_setengah_jadi WHERE id_bsj != '$id_induk' ORDER BY nama_bsj ASC");
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
                                    <i class="fa fa-plus"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </form>

                        <hr>
                        
                        <form action="simpan_bom.php" method="POST">
                            <input type="hidden" name="id_induk" value="<?= $id_induk ?>">
                            <input type="hidden" name="tipe_bom" value="BSJ">
                            <input type="hidden" name="source_from" value="<?= $from ?>">

                            <div class="row mb-4 bg-light p-3 rounded border">
                                <div class="col-md-12">
                                    <label class="fw-bold text-primary mb-2">TARGET HASIL PRODUKSI (YIELD)</label>
                                    <div class="input-group">
                                        <input type="number" step="any" name="target_hasil" class="form-control form-control-lg fw-bold" placeholder="Contoh: 5.5" required>
                                        <span class="input-group-text"><?= htmlspecialchars($data_bsj['nama_satuan']) ?></span>
                                    </div>
                                    <small class="text-muted">Berapa porsi yang dihasilkan dari rincian bahan di bawah ini?</small>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3 mt-4">Rincian Bahan dalam Resep</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th width="50">No</th>
                                            <th>Bahan</th>
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
                                                    <td><?= htmlspecialchars($item['nama_bahan']) ?></td>
                                                    <td class='text-center fw-bold'><?= $item['qty'] ?></td>
                                                    <td class='text-center'><?= htmlspecialchars($item['satuan']) ?></td>
                                                    <td class='text-center'>
                                                        <a href='buat_bom_bsj.php?id=<?= $id_induk ?>&hapus_idx=<?= $idx ?>&from=<?= $from ?>' class='btn btn-danger btn-sm'>
                                                            <i class='fa fa-trash'></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                                $no++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted'>Keranjang masih kosong.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <a href="<?= $btn_kembali ?>" class="btn btn-secondary px-4">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>

                                <?php if (!empty($_SESSION['keranjang_bom'][$id_induk])): ?>
                                    <button type="submit" name="simpan_permanen" class="btn btn-primary px-4 shadow">
                                        <i class="fa fa-save"></i> SIMPAN RESEP
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
    // 1. Jalankan Dropdown Search
    $('.select2').select2({
        placeholder: "-- Pilih Bahan --",
        width: '100%'
    });

    // 2. Logika Satuan Otomatis
    $('#pilihan_bahan').change(function(){
        var p = $(this).val();
        if(p != "") {
            $.post('get_satuan_ajax.php', {pilihan: p}, function(res){ 
                try {
                    var data = JSON.parse(res);
                    $('#id_satuan_hidden').val(data.id);
                    $('#nama_satuan_tampil').val(data.nama);
                } catch (e) {
                    console.error("Error data satuan:", res);
                }
            });
        } else {
            $('#id_satuan_hidden').val('');
            $('#nama_satuan_tampil').val('');
        }
    });
});
</script>
</body>
</html>