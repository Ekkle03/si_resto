<?php
session_start();
include("../config/koneksi_mysql.php");

if (!isset($_GET['id']) || !isset($_GET['tipe'])) {
    header("Location: master_bom.php?msg=Parameter tidak lengkap");
    exit();
}

$id_induk = (int)$_GET['id'];
$tipe_bom = mysqli_real_escape_string($koneksi, $_GET['tipe']);

// Ambil Nama Produk & Satuan
if ($tipe_bom == 'MENU') {
    $q_prod = mysqli_query($koneksi, "SELECT m.nama_menu as nama, s.nama_satuan FROM master_menu m JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE m.id_menu = '$id_induk'");
} else {
    $q_prod = mysqli_query($koneksi, "SELECT m.nama_bsj as nama, s.nama_satuan FROM master_bahan_setengah_jadi m JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE m.id_bsj = '$id_induk'");
}
$data_produk = mysqli_fetch_assoc($q_prod);

// Ambil Target Hasil yang ada di salah satu baris BOM (karena semua baris untuk 1 id_induk yield-nya sama)
$q_yield = mysqli_query($koneksi, "SELECT target_hasil FROM master_bom WHERE id_induk = '$id_induk' AND tipe_bom = '$tipe_bom' LIMIT 1");
$data_yield = mysqli_fetch_assoc($q_yield);
$target_lama = $data_yield['target_hasil'] ?? 1;

// --- LOGIKA 1: TAMBAH ITEM BARU ---
if (isset($_POST['tambah_item'])) {
    $pilihan = explode('-', $_POST['pilihan_bahan']); 
    $tipe_item = $pilihan[0];
    $id_item = (int)$pilihan[1];
    $qty_input = floatval($_POST['qty_baru']);
    $id_satuan = $_POST['id_satuan_pilih']; 
    $yield_baru = floatval($_POST['target_hasil_update']); // Tangkap yield dari form
    
    // Generate kode BOM unik
    $kode_bom = "BOM-" . $tipe_bom . "-" . rand(100,999);

    // Update semua yield pada id_induk ini agar sinkron dengan yang baru diinput
    mysqli_query($koneksi, "UPDATE master_bom SET target_hasil = '$yield_baru' WHERE id_induk = '$id_induk' AND tipe_bom = '$tipe_bom'");

    if ($tipe_item == 'BB') {
        mysqli_query($koneksi, "INSERT INTO master_bom (kode_bom, tipe_bom, id_bb, id_induk, qty, id_satuan, target_hasil) 
                                VALUES ('$kode_bom', '$tipe_bom', '$id_item', '$id_induk', '$qty_input', '$id_satuan', '$yield_baru')");
    } else {
        mysqli_query($koneksi, "INSERT INTO master_bom (kode_bom, tipe_bom, id_bsj, id_induk, qty, id_satuan, target_hasil) 
                                VALUES ('$kode_bom', '$tipe_bom', '$id_item', '$id_induk', '$qty_input', '$id_satuan', '$yield_baru')");
    }
    header("Location: update_bom.php?id=$id_induk&tipe=$tipe_bom&msg=Bahan & Target Hasil berhasil diperbarui");
    exit();
}

// --- LOGIKA 2: UPDATE QTY & TARGET HASIL MASSAL ---
if (isset($_POST['update_resep'])) {
    $yield_final = floatval($_POST['target_hasil_update']); // Pakai floatval juga di sini
    
    foreach ($_POST['qty_edit'] as $id_bom => $qty_baru) {
        $id_bom = (int)$id_bom;
        $qty_baru = floatval($qty_baru);
        // Update Qty sekaligus update Target Hasil ke semua baris
        mysqli_query($koneksi, "UPDATE master_bom SET qty = '$qty_baru', target_hasil = '$yield_final' WHERE id_bom = '$id_bom'");
    }
    header("Location: master_bom.php?msg=Seluruh Resep & Target Hasil berhasil diperbarui");
    exit();
}

// --- QUERY DATA TABEL ---
$sql_bahan = "SELECT b.*, 
                COALESCE(bb.nama_bb, bsj.nama_bsj) as nama_bahan,
                s.nama_satuan as nama_satuan_pakai
              FROM master_bom b
              LEFT JOIN master_bahan_baku bb ON b.id_bb = bb.id_bb
              LEFT JOIN master_bahan_setengah_jadi bsj ON b.id_bsj = bsj.id_bsj
              LEFT JOIN master_satuan s ON b.id_satuan = s.id_satuan
              WHERE b.id_induk = '$id_induk' AND b.tipe_bom = '$tipe_bom'";
$query_bahan = mysqli_query($koneksi, $sql_bahan);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Divisi - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <!-- Fonts and icons -->
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
        .select2-container { display: block !important; width: 100% !important; }
        .select2-container--default .select2-selection--single {
            height: 40px !important; border: 1px solid #ebedf2 !important;
            padding-top: 5px; border-radius: 5px !important;
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
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Update Resep: <b><?= htmlspecialchars($data_produk['nama']) ?></b></h4></div>
                        <div class="card-body">
                            <?php if(isset($_GET['msg'])): ?>
                                <div class="alert alert-success alert-dismissible fade show auto-close" role="alert">
                                    <?= htmlspecialchars($_GET['msg']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="row align-items-end mb-4 p-3 bg-light rounded border">
                                <div class="col-md-4">
                                    <label class="fw-bold small">Pilih Bahan Baru</label>
                                    <select name="pilihan_bahan" id="pilihan_bahan" class="form-select" required>
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
                                <div class="col-md-2">
                                    <label class="fw-bold small">Qty</label>
                                    <input type="number" step="0.001" name="qty_baru" class="form-control" placeholder="0.000" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="fw-bold small">Satuan</label>
                                    <input type="hidden" name="id_satuan_pilih" id="id_satuan_hidden">
                                    <input type="text" id="nama_satuan_tampil" class="form-control bg-light" placeholder="Satuan" readonly>
                                </div>
                                <input type="hidden" name="target_hasil_update" class="val_target_hasil" value="<?= $target_lama ?>">
                                <div class="col-md-3"><button type="submit" name="tambah_item" class="btn btn-success w-100"><i class="fa fa-plus"></i> Tambah Bahan</button></div>
                            </form>

                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-12"> <?php if ($tipe_bom == 'BSJ'): ?>
                                            <div class="col-md-4 col-lg-3 p-0"> <div class="card border-primary shadow-sm mb-0">
                                                    <div class="card-body p-2">
                                                        <label class="fw-bold small mb-1 text-primary">
                                                            <i class="fas fa-bullseye me-1"></i> Target Hasil (Yield)
                                                        </label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="any" name="target_hasil_update" class="form-control fw-bold text-center" value="<?= floatval($target_lama) ?>" required>
                                                            <span class="input-group-text bg-primary text-white small">
                                                                <?= htmlspecialchars($data_produk['nama_satuan'] ?? 'Unit') ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="target_hasil_update" value="1">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover align-middle">
                                        <thead class="text-center bg-light">
                                            <tr>
                                                <th width="5%">NO</th>
                                                <th>NAMA BAHAN</th>
                                                <th width="20%">QTY</th>
                                                <th>SATUAN</th>
                                                <th width="10%">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; while($row = mysqli_fetch_assoc($query_bahan)): ?>
                                            <tr class="text-center">
                                                <td><?= $no++ ?></td>
                                                <td class="text-start fw-bold"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                                <td><input type="number" step="0.001" name="qty_edit[<?= $row['id_bom'] ?>]" value="<?= floatval($row['qty']) ?>" class="form-control text-center"></td>
                                                <td><?= htmlspecialchars($row['nama_satuan_pakai']) ?></td>
                                                <td><a href="delete_bom_item.php?id=<?= $row['id_bom'] ?>&back_id=<?= $id_induk ?>&back_tipe=<?= $tipe_bom ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus bahan?')"><i class="fa fa-trash"></i></a></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 d-flex justify-content-end gap-2">
                                    <a href="master_bom.php" class="btn btn-secondary px-4">Kembali</a>
                                    <button type="submit" name="update_resep" class="btn btn-primary px-4 shadow"><i class="fa fa-save"></i> Simpan Semua Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
            $(document).ready(function() {
                // 1. Inisialisasi Select2
                $('#pilihan_bahan').select2({
                    placeholder: "-- Cari Bahan --",
                    width: '100%'
                });

                // 2. AJAX Satuan Otomatis (JSON)
                $('#pilihan_bahan').change(function() {
                    var p = $(this).val();
                    if (p != "") {
                        $.post('get_satuan_ajax.php', { pilihan: p }, function(res) {
                            try {
                                var data = JSON.parse(res);
                                $('#id_satuan_hidden').val(data.id);
                                $('#nama_satuan_tampil').val(data.nama);
                            } catch (e) {
                                console.error("Gagal parse JSON satuan");
                            }
                        });
                    }
                });

                // 3. Auto-close Alert
                window.setTimeout(function() { 
                    $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); }); 
                }, 3000);
            });
    </script>
</body>
</html>