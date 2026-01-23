<?php
// menu_keluar.php - Menampilkan riwayat menu keluar
session_start();
include("../config/koneksi_mysql.php");

// Ambil data riwayat
$riwayat = [];
$sql = "
    SELECT 
        mk.id_menu, 
        mk.tanggal_keluar, 
        mk.catatan,
        (SELECT COUNT(*) FROM menu_keluar_detail mkd WHERE mkd.id_menu = mk.id_menu) AS jumlah_jenis_menu
    FROM menu_keluar mk
    ORDER BY mk.tanggal_keluar DESC
";
$q = mysqli_query($koneksi, $sql);
if ($q) while ($r = mysqli_fetch_assoc($q)) $riwayat[] = $r;

// Ambil data menu untuk dropdown
$menu_list = [];
$q_menu = mysqli_query($koneksi, "SELECT i.id_item, i.nama_item, s.nama_satuan FROM master_item i LEFT JOIN master_satuan s ON i.id_satuan=s.id_satuan WHERE i.jenis_item = 'menu' ORDER BY i.nama_item");
if ($q_menu) while($r = mysqli_fetch_assoc($q_menu)) $menu_list[] = $r;

$pesan = '';
if (isset($_SESSION['flash_msg'])) {
    $pesan = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Menu Keluar - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script> WebFont.load({ google: { "families": ["Public Sans:300,400,500,600,700"] }, custom: { "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], "urls": ["assets/css/fonts.min.css"], }, }); </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <div class="main-header"><!-- (Header content) --></div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Transaksi Menu Keluar</h3>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title">Riwayat Menu Keluar</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addMenuKeluarModal">
                                        <i class="fa fa-plus"></i> Tambah Transaksi
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($pesan): ?><div class="alert alert-info" role="alert"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
                                <div class="table-responsive">
                                    <table id="tabel-menu-keluar" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tanggal</th>
                                                <th>Keterangan</th>
                                                <th class="text-center">Jumlah Menu</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($riwayat as $r): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['id_menu']) ?></td>
                                                <td><?= date('d-m-Y H:i', strtotime($r['tanggal_keluar'])) ?></td>
                                                <td><?= htmlspecialchars($r['catatan']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($r['jumlah_jenis_menu']) ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-info btn-sm btn-detail" data-id="<?= $r['id_menu'] ?>" title="Lihat Detail"><i class="fa fa-eye"></i></button>
                                                    <a href="delete_menu_keluar.php?id=<?= $r['id_menu'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus transaksi ini? Stok akan dikembalikan.')" title="Hapus"><i class="fa fa-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
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

<!-- Modal Tambah Menu Keluar -->
<div class="modal fade" id="addMenuKeluarModal" tabindex="-1" aria-labelledby="addMenuKeluarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="add_menu_keluar.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuKeluarLabel">Tambah Transaksi Menu Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Tanggal</label>
                            <input type="datetime-local" name="tanggal_keluar" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Catatan (Opsional)</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Contoh: Meja 5">
                        </div>
                    </div>
                    <hr>
                    <h5 class="mb-3">Daftar Menu</h5>
                    <div id="detail-item-container">
                        <!-- Baris pertama -->
                        <div class="row gx-2 mb-2 detail-item-row">
                            <div class="col-md-7">
                                <select name="id_item[]" class="form-select" required>
                                    <option value="">-- Pilih Menu --</option>
                                    <?php foreach($menu_list as $menu): ?>
                                    <option value="<?= $menu['id_item'] ?>"><?= htmlspecialchars($menu['nama_item']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="jumlah_keluar[]" class="form-control" placeholder="Jumlah" min="1" required>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btn-add-row" class="btn btn-secondary btn-sm mt-2"><i class="fa fa-plus"></i> Tambah Baris</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Menu Keluar -->
<div class="modal fade" id="detailMenuKeluarModal" tabindex="-1" aria-labelledby="detailMenuKeluarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="detailMenuKeluarLabel">Detail Menu Keluar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="detail-modal-body">
                <!-- Konten detail akan dimuat di sini via AJAX -->
                <p class="text-center">Memuat data...</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script><script src="assets/js/core/popper.min.js"></script><script src="assets/js/core/bootstrap.min.js"></script><script src="assets/js/plugin/datatables/datatables.min.js"></script><script src="assets/js/kaiadmin.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabel-menu-keluar').DataTable();

    // Template baris baru
    const newRowTemplate = `
        <div class="row gx-2 mb-2 detail-item-row">
            <div class="col-md-7">
                <select name="id_item[]" class="form-select" required>
                    <option value="">-- Pilih Menu --</option>
                    <?php foreach($menu_list as $menu): ?>
                    <option value="<?= $menu['id_item'] ?>"><?= htmlspecialchars($menu['nama_item']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="jumlah_keluar[]" class="form-control" placeholder="Jumlah" min="1" required>
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
            </div>
        </div>`;

    // Tambah baris
    $('#btn-add-row').on('click', function() {
        $('#detail-item-container').append(newRowTemplate);
    });

    // Hapus baris
    $(document).on('click', '.btn-remove-row', function() {
        // Jangan hapus jika hanya ada satu baris
        if ($('.detail-item-row').length > 1) {
            $(this).closest('.detail-item-row').remove();
        }
    });

    // Tampilkan detail
    $('.btn-detail').on('click', function(){
        const id_menu = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('detailMenuKeluarModal'));
        const body = $('#detail-modal-body');
        
        body.html('<p class="text-center">Memuat data...</p>');
        modal.show();
        
        $.ajax({
            url: 'detail_menu_keluar.php',
            type: 'GET',
            data: { id: id_menu },
            success: function(response) {
                body.html(response);
            },
            error: function() {
                body.html('<p class="text-center text-danger">Gagal memuat detail.</p>');
            }
        });
    });
});
</script>
</body>
</html>
