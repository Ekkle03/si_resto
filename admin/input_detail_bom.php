<?php
// input_detail_bom.php — Halaman canggih untuk merakit resep dengan konversi satuan.
session_start();
include("../config/koneksi_mysql.php");

// Fungsi untuk format angka yang lebih rapi
function format_qty($val) {
    return rtrim(rtrim(number_format($val, 3, ',', '.'), '0'), ',');
}

// Pastikan produk_id ada dan valid
$produk_id = isset($_REQUEST['produk_id']) ? (int)$_REQUEST['produk_id'] : 0;
if ($produk_id <= 0) {
    header("Location: bom.php");
    exit;
}

// Inisialisasi keranjang BOM di session
if (!isset($_SESSION['keranjang_bom'])) $_SESSION['keranjang_bom'] = [];
if (!isset($_SESSION['keranjang_bom'][$produk_id])) {
    // Jika baru pertama kali, load data BOM yang sudah ada ke session
    $_SESSION['keranjang_bom'][$produk_id] = [];
    $q_exist = mysqli_query($koneksi, "SELECT komponen_id, qty FROM tabel_bom WHERE produk_id = $produk_id");
    if($q_exist) {
        while($r_exist = mysqli_fetch_assoc($q_exist)){
            $_SESSION['keranjang_bom'][$produk_id][$r_exist['komponen_id']] = [
                'id_item' => $r_exist['komponen_id'],
                'qty' => $r_exist['qty'] // Qty di db adalah dalam satuan dasar
            ];
        }
    }
}

// Proses Aksi (Tambah/Hapus Komponen dari Keranjang Session)
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action === 'tambah') {
    $komponen_id = isset($_POST['komponen_id']) ? (int)$_POST['komponen_id'] : 0;
    $qty_input = isset($_POST['qty']) ? (float)$_POST['qty'] : 0;
    $satuan_input_id = isset($_POST['satuan_id']) ? (int)$_POST['satuan_id'] : 0;
    
    if ($komponen_id > 0 && $qty_input > 0 && $satuan_input_id > 0) {
        // Ambil satuan dasar dari komponen
        $q_base_unit = mysqli_query($koneksi, "SELECT id_satuan FROM master_item WHERE id_item = $komponen_id");
        $base_unit_id = mysqli_fetch_assoc($q_base_unit)['id_satuan'];
        
        $qty_to_save = $qty_input; // Default jika satuannya sama
        
        // Jika satuan input berbeda dari satuan dasar, lakukan konversi
        if ($satuan_input_id != $base_unit_id) {
            // Cari faktor konversi dari satuan dasar (satuan_ke) ke satuan input (satuan_dari)
            $q_conv = mysqli_query($koneksi, "SELECT faktor_konversi FROM tabel_satuan_konversi WHERE id_item = $komponen_id AND satuan_dari = $base_unit_id AND satuan_ke = $satuan_input_id");
            if(mysqli_num_rows($q_conv) > 0) {
                 $faktor = (float)mysqli_fetch_assoc($q_conv)['faktor_konversi'];
                 $qty_to_save = $qty_input / $faktor; // Konversi dari satuan kecil (gram) ke satuan dasar (bungkus)
            } else {
                 // Coba cari arah sebaliknya
                 $q_conv_rev = mysqli_query($koneksi, "SELECT faktor_konversi FROM tabel_satuan_konversi WHERE id_item = $komponen_id AND satuan_dari = $satuan_input_id AND satuan_ke = $base_unit_id");
                 if(mysqli_num_rows($q_conv_rev) > 0) {
                     $faktor = (float)mysqli_fetch_assoc($q_conv_rev)['faktor_konversi'];
                     $qty_to_save = $qty_input * $faktor; // Konversi dari satuan besar (bungkus) ke satuan dasar (gram)
                 }
            }
        }
        
        // Tambahkan ke session dalam satuan dasar
        $_SESSION['keranjang_bom'][$produk_id][$komponen_id] = [
            'id_item' => $komponen_id,
            'qty' => $qty_to_save
        ];
    }
} 
elseif ($action === 'hapus') {
    $komponen_id_to_delete = isset($_GET['hapus_komponen_id']) ? (int)$_GET['hapus_komponen_id'] : 0;
    if (isset($_SESSION['keranjang_bom'][$produk_id][$komponen_id_to_delete])) {
        unset($_SESSION['keranjang_bom'][$produk_id][$komponen_id_to_delete]);
    }
}

// Ambil info produk yang sedang dikelola (termasuk deskripsi)
$produk_info = null;
$q_produk = mysqli_query($koneksi, "SELECT nama_item, deskripsi FROM master_item WHERE id_item = $produk_id");
if ($q_produk) $produk_info = mysqli_fetch_assoc($q_produk);

// Ambil semua komponen yang bisa dipilih
$komponen_kandidat = [];
// Ambil semua satuan
$semua_satuan = [];
// Ambil semua konversi untuk JS
$konversi_map = [];

// Query untuk komponen kandidat
$q_komp = mysqli_query($koneksi, "SELECT id_item, nama_item FROM master_item WHERE jenis_item IN ('bahan baku', 'bahan setengah jadi') AND id_item <> $produk_id ORDER BY nama_item");
if ($q_komp) while($r = mysqli_fetch_assoc($q_komp)) $komponen_kandidat[$r['id_item']] = $r['nama_item'];

// Query untuk semua satuan
$q_satuan = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan");
if ($q_satuan) while($r = mysqli_fetch_assoc($q_satuan)) $semua_satuan[$r['id_satuan']] = $r['nama_satuan'];

// Query untuk semua konversi
$q_konv = mysqli_query($koneksi, "
    SELECT sk.id_item, sk.satuan_dari, s_dari.nama_satuan AS nama_dari, sk.satuan_ke, s_ke.nama_satuan AS nama_ke, sk.faktor_konversi 
    FROM tabel_satuan_konversi sk
    JOIN master_satuan s_dari ON sk.satuan_dari = s_dari.id_satuan
    JOIN master_satuan s_ke ON sk.satuan_ke = s_ke.id_satuan
");
if($q_konv){
    while($r = mysqli_fetch_assoc($q_konv)){
        if(!isset($konversi_map[$r['id_item']])) $konversi_map[$r['id_item']] = [];
        // Simpan konversi dua arah agar mudah dicari
        $konversi_map[$r['id_item']][] = ['id' => $r['satuan_dari'], 'nama' => $r['nama_dari']];
        $konversi_map[$r['id_item']][] = ['id' => $r['satuan_ke'], 'nama' => $r['nama_ke']];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Rakit Resep BOM - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />
    
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { "families": ["Public Sans:300,400,500,600,700"] },
            custom: {
                "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                "urls": ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <div class="main-header">
             <!-- (Header content goes here) -->
        </div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Rakit Resep (Bill of Material)</h3>
                </div>
                <div class="row">
                    <!-- Kolom Kiri: Form untuk Menambah Komponen -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Tambah Komponen</h4></div>
                            <div class="card-body">
                                <form method="POST" action="input_detail_bom.php">
                                    <input type="hidden" name="action" value="tambah">
                                    <input type="hidden" name="produk_id" value="<?= htmlspecialchars($produk_id) ?>">
                                    <div class="form-group">
                                        <label for="komponen_id">Pilih Komponen</label>
                                        <select class="form-select" id="komponen_id" name="komponen_id" required>
                                            <option value="">-- Cari & Pilih Bahan --</option>
                                            <?php foreach ($komponen_kandidat as $id => $nama): ?>
                                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="qty">Jumlah (Qty)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="qty" name="qty" step="0.001" min="0.001" required placeholder="e.g., 50">
                                            <select class="form-select" name="satuan_id" id="satuan_id" style="max-width: 120px;" required>
                                                <option value="">Pilih Satuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100"><i class="fa fa-plus"></i> Tambah ke Daftar</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Daftar Komponen yang Akan Disimpan -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">Daftar Resep untuk: <span class="text-primary fw-bold"><?= $produk_info ? htmlspecialchars($produk_info['nama_item']) : '' ?></span></h4></div>
                            <div class="card-body">
                                <?php if ($produk_info && !empty($produk_info['deskripsi'])): ?>
                                    <div class="alert alert-info" role="alert">
                                        <strong>Info:</strong> <?= htmlspecialchars($produk_info['deskripsi']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Komponen</th>
                                                <th class="text-end" style="width: 25%">Jumlah (Qty)</th>
                                                <th class="text-center" style="width:15%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($_SESSION['keranjang_bom'][$produk_id])): ?>
                                                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada komponen ditambahkan.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($_SESSION['keranjang_bom'][$produk_id] as $komponen): 
                                                    $item_id = $komponen['id_item'];
                                                    $q_detail = mysqli_query($koneksi, "SELECT i.nama_item, s.nama_satuan FROM master_item i JOIN master_satuan s ON i.id_satuan=s.id_satuan WHERE i.id_item = $item_id");
                                                    $detail = mysqli_fetch_assoc($q_detail);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($detail['nama_item']) ?></td>
                                                    <td class="text-end"><?= format_qty($komponen['qty']) ?> <?= htmlspecialchars($detail['nama_satuan']) ?></td>
                                                    <td class="text-center">
                                                        <a href="input_detail_bom.php?produk_id=<?= htmlspecialchars($produk_id) ?>&action=hapus&hapus_komponen_id=<?= htmlspecialchars($komponen['id_item']) ?>" class="btn btn-danger btn-sm" title="Hapus Komponen"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="bom.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Kembali</a>
                                    <form method="POST" action="bom_add.php" onsubmit="return confirm('Anda yakin ingin menyimpan resep ini? Semua data resep lama akan diganti.')">
                                        <input type="hidden" name="produk_id" value="<?= htmlspecialchars($produk_id) ?>">
                                        <button type="submit" class="btn btn-success btn-round" <?= empty($_SESSION['keranjang_bom'][$produk_id]) ? 'disabled' : '' ?>><i class="fa fa-save"></i> Simpan Resep Ini</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Data dari PHP untuk digunakan di JavaScript
const semuaSatuan = <?= json_encode($semua_satuan) ?>;
const konversiMap = <?= json_encode($konversi_map) ?>;

$(document).ready(function() {
    // Inisialisasi Select2
    $('#komponen_id').select2({ theme: 'bootstrap-5' });

    // Event saat pilihan komponen berubah
    $('#komponen_id').on('change', function() {
        const selectedItemId = $(this).val();
        const satuanDropdown = $('#satuan_id');
        
        // Kosongkan dropdown satuan
        satuanDropdown.html('<option value="">Pilih Satuan</option>');

        if (selectedItemId && konversiMap[selectedItemId]) {
            // Jika ada aturan konversi untuk item ini, tampilkan semua satuannya
            let uniqueSatuan = {};
            konversiMap[selectedItemId].forEach(function(satuan) {
                if (!uniqueSatuan[satuan.id]) {
                    satuanDropdown.append(new Option(satuan.nama, satuan.id));
                    uniqueSatuan[satuan.id] = true;
                }
            });
        }
        
        satuanDropdown.prop('disabled', false);
    });
});
</script>
</body>
</html>

