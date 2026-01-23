<?php
// bom.php — Halaman utama Master BOM, dengan tampilan CRUD
session_start();
include("../config/koneksi_mysql.php");

/* helper function to get component details for the modal */
function get_detail_bom($koneksi, $produk_id){
    $rows = [];
    $stmt = mysqli_prepare($koneksi, "
        SELECT i.nama_item AS komponen, s.nama_satuan AS satuan, b.qty
        FROM tabel_bom b
        JOIN master_item i ON i.id_item=b.komponen_id
        LEFT JOIN master_satuan s ON s.id_satuan=i.id_satuan
        WHERE b.produk_id=?
        ORDER BY i.nama_item
    ");
    mysqli_stmt_bind_param($stmt, "i", $produk_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) while($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($stmt);
    return $rows;
}

/* ========================= AMBIL DATA HEADER BOM (RESEP YANG SUDAH ADA) ========================= */
$headers = [];
// Query untuk mengambil daftar produk yang sudah memiliki resep
$sql = "
    SELECT 
        MIN(b.id_bom) AS id_bom,
        b.produk_id,
        p.nama_item AS produk
    FROM tabel_bom b
    JOIN master_item p ON p.id_item = b.produk_id
    GROUP BY b.produk_id, p.nama_item
    ORDER BY p.nama_item ASC
";
$q = mysqli_query($koneksi, $sql);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $headers[] = $r;
    }
}

/* ========================= AMBIL DATA KANDIDAT PRODUK ========================= */
// Item yang bisa dibuatkan resep (HANYA 'bahan setengah jadi' dan 'menu')
$produk_kandidat = [];
$qk = mysqli_query($koneksi, "
    SELECT id_item, nama_item 
    FROM master_item 
    WHERE jenis_item IN ('bahan setengah jadi','menu')
    ORDER BY nama_item
");
if ($qk) {
    while($rk = mysqli_fetch_assoc($qk)) {
        $produk_kandidat[] = $rk;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master BOM - Sistem Resto</title>
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
    <style>.btn-outline-primary-thicker{border-width:2px!important;font-weight:500!important}</style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <div class="main-header">
             <!-- (Header content from your original file will go here) -->
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
                                <h4 class="card-title mb-0">Data Master BOM</h4>
                                <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addHeaderModal">
                                    <i class="fa fa-plus"></i> Buat Resep Baru
                                </button>
                            </div>

                            <div class="card-body">
                                <?php if (isset($_SESSION['flash_msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= htmlspecialchars($_SESSION['flash_msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php unset($_SESSION['flash_msg']); ?>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width:15%" class="text-center">ID BOM</th>
                                                <th style="width:25%" class="text-center">Item</th>
                                                <th style="width:15%" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($headers as $h): 
                                                $pid = (int)$h['produk_id'];
                                                // Rakit HTML detail komponen untuk modal
                                                $detail_komponen = get_detail_bom($koneksi, $pid);
                                                ob_start();
                                            ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light"><tr><th>Komponen</th><th class="text-end" style="width:20%">Qty</th><th style="width:25%">Satuan</th></tr></thead>
                                                        <tbody>
                                                            <?php foreach($detail_komponen as $d): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($d['komponen']) ?></td>
                                                                <td class="text-end"><?= number_format($d['qty'], 3, ',', '.') ?></td>
                                                                <td><?= htmlspecialchars($d['satuan'] ?? '-') ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php 
                                                $detail_html = ob_get_clean();
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= (int)$h['id_bom'] ?></td>
                                                <td><?= htmlspecialchars($h['produk']) ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-info btn-sm btn-detail" data-bs-toggle="modal" data-bs-target="#detailBomModal" data-produk-nama="<?= htmlspecialchars($h['produk']) ?>" data-detail-html='<?= htmlspecialchars($detail_html, ENT_QUOTES) ?>' title="Lihat Rincian Resep">
                                                        <i class="fa fa-eye"></i> Detail
                                                    </button>
                                                    <a href="input_detail_bom.php?produk_id=<?= $pid ?>" class="btn btn-primary btn-sm" title="Ubah Resep">
                                                        <i class="fa fa-edit"></i> Update
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-bs-toggle="modal" data-bs-target="#deleteBomModal" data-produk-id="<?= $pid ?>" title="Hapus Resep">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
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

<!-- Modal: Langkah 1 - Pilih Produk untuk Dibuatkan Resep -->
<div class="modal fade" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="input_detail_bom.php">
                <div class="modal-header">
                    <h5 class="modal-title">Langkah 1: Pilih Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Pilih produk yang akan dibuatkan resepnya:</label>
                    <select class="form-select" name="produk_id" required>
                        <option value="">-- Pilih dari Menu atau Bahan Setengah Jadi --</option>
                        <?php foreach($produk_kandidat as $p): ?>
                            <option value="<?= htmlspecialchars($p['id_item']) ?>"><?= htmlspecialchars($p['nama_item']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Setelah memilih, Anda akan diarahkan ke halaman khusus untuk merakit resepnya.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Lanjut ke Peracikan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail BOM -->
<div class="modal fade" id="detailBomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalTitle">Rincian BOM</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailModalBody">
        <!-- Content will be injected by JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Delete BOM -->
<div class="modal fade" id="deleteBomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Anda yakin ingin menghapus seluruh resep untuk produk ini? Aksi ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <a href="#" id="deleteConfirmLink" class="btn btn-danger">Ya, Hapus</a>
      </div>
    </div>
  </div>
</div>


<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script>
$(function(){
    $('#basic-datatables').DataTable({ pageLength: 25 });

    // Handle Detail Modal
    $('.btn-detail').on('click', function() {
        var produkNama = $(this).data('produk-nama');
        var detailHtml = $(this).data('detail-html');
        $('#detailModalTitle').text('Rincian BOM untuk ' + produkNama);
        $('#detailModalBody').html(detailHtml);
    });

    // Handle Delete Modal
    $('.btn-delete').on('click', function() {
        var produkId = $(this).data('produk-id');
        var deleteUrl = 'bom_delete.php?produk_id=' + produkId;
        $('#deleteConfirmLink').attr('href', deleteUrl);
    });
});
</script>
</body>
</html>

