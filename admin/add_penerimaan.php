<?php
// add_penerimaan.php - Form untuk merakit daftar penerimaan barang (DESAIN BARU)
session_start();
include("../config/koneksi_mysql.php");

// Logika untuk mereset keranjang jika diminta atau jika halaman diakses langsung
if ((isset($_GET['action']) && $_GET['action'] === 'reset') || !isset($_SESSION['keranjang_penerimaan'])) {
    $_SESSION['keranjang_penerimaan'] = ['header' => [], 'detail' => []];
    if (isset($_GET['action'])) {
        header("Location: add_penerimaan.php");
        exit;
    }
}

// Proses Aksi (Tambah/Hapus item dari keranjang)
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action === 'tambah_item') {
    // Saat item ditambahkan, informasi header juga ikut diperbarui dari form yang sama
    $_SESSION['keranjang_penerimaan']['header'] = [
        'tanggal' => $_POST['tanggal_penerimaan'],
        'keterangan' => $_POST['keterangan']
    ];

    $item_id = (int)$_POST['item_id'];
    $jumlah = (float)$_POST['jumlah_penerimaan'];
    if ($item_id > 0 && $jumlah > 0) {
        if (isset($_SESSION['keranjang_penerimaan']['detail'][$item_id])) {
            $_SESSION['keranjang_penerimaan']['detail'][$item_id]['jumlah'] += $jumlah;
        } else {
            $_SESSION['keranjang_penerimaan']['detail'][$item_id] = [
                'id_item' => $item_id,
                'jumlah' => $jumlah
            ];
        }
    }
    header("Location: add_penerimaan.php");
    exit;
} elseif ($action === 'hapus_item') {
    $item_id_to_delete = (int)$_GET['hapus_item_id'];
    if (isset($_SESSION['keranjang_penerimaan']['detail'][$item_id_to_delete])) {
        unset($_SESSION['keranjang_penerimaan']['detail'][$item_id_to_delete]);
    }
    header("Location: add_penerimaan.php");
    exit;
}

// Ambil data untuk dropdown item (hanya bahan baku)
$item_kandidat = [];
$q_item = mysqli_query($koneksi, "
    SELECT i.id_item, i.nama_item, s.nama_satuan 
    FROM master_item i 
    LEFT JOIN master_satuan s ON i.id_satuan = s.id_satuan
    WHERE i.jenis_item = 'bahan baku' 
    ORDER BY i.nama_item
");
if ($q_item) {
    while ($r = mysqli_fetch_assoc($q_item)) {
        $item_kandidat[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Penerimaan Barang - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { "families": ["Public Sans:300,400,500,600,700"] },
            custom: { "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], "urls": ["assets/css/fonts.min.css"], },
        });
    </script>
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <div class="main-header">
            <!-- (Header content) -->
        </div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Tambah Penerimaan Barang Baru</h3>
                    <ul class="breadcrumbs">
                        <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="penerimaan.php">Penerimaan Barang</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a>Tambah Baru</a></li>
                    </ul>
                </div>
                
                <form method="POST" action="add_penerimaan.php">
                    <input type="hidden" name="action" value="tambah_item">
                    
                    <!-- CARD 1: INFORMASI TRANSAKSI -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Informasi Transaksi</div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal_penerimaan">Tanggal Penerimaan</label>
                                        <input type="date" class="form-control" id="tanggal_penerimaan" name="tanggal_penerimaan" value="<?= isset($_SESSION['keranjang_penerimaan']['header']['tanggal']) ? date('Y-m-d', strtotime($_SESSION['keranjang_penerimaan']['header']['tanggal'])) : date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="keterangan">Keterangan</label>
                                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Isi No. Surat Jalan atau Keterangan (misal: Beli dari Pasar Pagi)"><?= $_SESSION['keranjang_penerimaan']['header']['keterangan'] ?? '' ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: TAMBAH ITEM -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Tambah Item Diterima</div>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="item_id">Pilih Item (Bahan Baku)</label>
                                        <select class="form-select" id="item_id" name="item_id">
                                            <option value="">-- Cari & Pilih Item --</option>
                                            <?php foreach($item_kandidat as $item): ?>
                                            <option value="<?= $item['id_item'] ?>" data-satuan="<?= htmlspecialchars($item['nama_satuan']) ?>"><?= htmlspecialchars($item['nama_item']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                     <div class="form-group">
                                        <label for="satuan">Satuan</label>
                                        <input type="text" class="form-control" id="satuan" placeholder="Otomatis" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="jumlah_penerimaan">Jumlah</label>
                                        <input type="number" step="0.01" class="form-control" id="jumlah_penerimaan" name="jumlah_penerimaan" placeholder="Contoh: 50.5">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- CARD 3: DAFTAR ITEM SEMENTARA -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Daftar Barang yang Akan Disimpan</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">Item</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center" style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($_SESSION['keranjang_penerimaan']['detail'])): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-5">Belum ada item ditambahkan.</td></tr>
                                    <?php else: 
                                        $item_map = [];
                                        foreach($item_kandidat as $ik) $item_map[$ik['id_item']] = $ik;
                                    ?>
                                        <?php foreach ($_SESSION['keranjang_penerimaan']['detail'] as $item): 
                                            $nama_item = $item_map[$item['id_item']]['nama_item'] ?? 'Item Tidak Dikenal';
                                            $satuan = $item_map[$item['id_item']]['nama_satuan'] ?? '';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($nama_item) ?></td>
                                            <td><?= rtrim(rtrim(number_format($item['jumlah'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($satuan) ?></td>
                                            <td class="text-center">
                                                <a href="add_penerimaan.php?action=hapus_item&hapus_item_id=<?= $item['id_item'] ?>" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="add_penerimaan.php?action=reset" class="btn btn-warning" onclick="return confirm('Yakin ingin mereset semua isian form ini?')">
                                <i class="fa fa-sync"></i> Reset Form
                            </a>
                            <!-- Form terpisah untuk submit akhir -->
                            <form method="POST" action="simpan_penerimaan.php" onsubmit="return confirm('Anda yakin ingin menyimpan data penerimaan ini ke database?')">
                                <button type="submit" class="btn btn-success btn-round" <?= empty($_SESSION['keranjang_penerimaan']['detail']) ? 'disabled' : '' ?>>
                                    <i class="fa fa-save"></i> Simpan Semua Penerimaan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- Core JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Inisialisasi Select2
        $('#item_id').select2({
            theme: 'bootstrap-5'
        });

        // Event listener saat pilihan item berubah
        $('#item_id').on('change', function() {
            // Ambil data 'satuan' dari atribut data-satuan pada option yang dipilih
            var satuan = $(this).find(':selected').data('satuan');
            
            // Tampilkan satuan di input #satuan
            if (satuan) {
                $('#satuan').val(satuan);
            } else {
                $('#satuan').val(''); // Kosongkan jika tidak ada item terpilih
            }
        });
    });
</script>
</body>
</html>

