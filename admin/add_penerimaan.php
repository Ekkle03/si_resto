<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Generate Kode Penerimaan Unik (Format Baru: RCV + DDMMYY + XX)
$tgl = date('dmy'); // Menghasilkan format seperti 150426
$prefix = "RCV" . $tgl;

// Cari kode terakhir di hari ini
$query_kode = mysqli_query($koneksi, "SELECT MAX(kode_penerimaan) as max_kode FROM penerimaan WHERE kode_penerimaan LIKE '$prefix%'");
$data_kode = mysqli_fetch_assoc($query_kode);
$last_kode = $data_kode['max_kode'] ?? ''; 

// Ambil 2 digit terakhir, misal RCV15042601 -> ambil '01'
$noUrut = ($last_kode != '') ? (int) substr($last_kode, -2) : 0;
$noUrut++;

// Gabungkan jadi kode baru
$kode_otomatis = $prefix . sprintf("%02s", $noUrut);

// 2. Hanya ambil PB yang BELUM PERNAH diterima
$sql_pb = "SELECT id_pembelian, kode_pembelian 
           FROM pembelian 
           WHERE id_pembelian NOT IN (SELECT id_pembelian FROM penerimaan WHERE id_pembelian IS NOT NULL)
           ORDER BY id_pembelian DESC";
$query_pb = mysqli_query($koneksi, $sql_pb);

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
    <title>Penerimaan - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: { families: [ "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons" ], urls: ["assets/css/fonts.min.css"] },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .table-detail thead th { background: #f8f9fa !important; color: #495057 !important; font-weight: 700 !important; text-transform: uppercase; font-size: 12px; border-bottom: 2px solid #dee2e6 !important; }
        .input-qty, .input-qty-rusak { font-weight: 700; text-align: center; }
        
        /* CSS Khusus biar Header nempel dan tabel bisa discroll rapi */
        .table-scrollable { max-height: 450px; overflow-y: auto; }
        .table-scrollable thead th { position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
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
                                <div class="avatar-sm">
                                    <img src="<?= $foto ?>" alt="..." class="avatar-img rounded-circle" onerror="this.src='assets/img/profil/default.png'"/>
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
                                            <div class="avatar-lg"><img src="<?= $foto ?>" alt="image profile" class="avatar-img rounded" onerror="this.src='assets/img/profil/default.png'"/></div>
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
                        <h3 class="fw-bold mb-1">Input Penerimaan Barang</h3>
                        <span class="text-muted">Nota: <b><?= $kode_otomatis ?></b> | Tgl: <?= date('d/m/Y') ?></span>
                    </div>
                    <div class="d-flex" style="gap: 8px;">
                        <button type="button" id="btn-trigger-simpan" class="btn btn-success fw-bold px-4">
                            <i class="fas fa-save me-1"></i> SIMPAN
                        </button>
                        <a href="penerimaan.php" class="btn btn-white border fw-bold text-danger">BATAL</a>
                    </div>
                </div>

                <form id="formPenerimaan" action="proses_add_penerimaan.php" method="POST" enctype="multipart/form-data">
                    <div class="card card-round shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="row">
                                <input type="hidden" name="kode_penerimaan" value="<?= $kode_otomatis ?>">
                                <div class="col-md-3 mb-3">
                                    <label class="fw-bold text-muted small">PILIH KODE PEMBELIAN (PB)</label>
                                    <select name="id_pembelian" id="id_pembelian" class="form-select form-control" required>
                                        <option value="">-- Pilih Nomor PB --</option>
                                        <?php while($pb = mysqli_fetch_assoc($query_pb)): ?>
                                            <option value="<?= $pb['id_pembelian'] ?>"><?= $pb['kode_pembelian'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="fw-bold text-muted small">TANGGAL TERIMA</label>
                                    <input type="date" name="tgl_terima" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">KETERANGAN TAMBAHAN</label>
                                    <textarea name="keterangan" class="form-control" rows="1" placeholder="Opsional..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-round shadow-sm border-0">
                        <div class="card-header bg-white">
                            <div class="card-title fw-bold">
                                Rincian Item Diterima 
                                <span class="text-muted fw-normal" style="font-size: 12px; margin-left: 10px;">(Isi Qty Waste jika ada barang cacat)</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive table-scrollable">
                                <table class="table table-hover table-bordered table-detail mb-0">
                                    <thead class="text-center">
                                        <tr>
                                            <th style="width: 15%;">KODE BAHAN</th>
                                            <th class="text-start" style="width: 30%; padding-left: 20px !important;">NAMA BAHAN BAKU</th>
                                            <th style="width: 12%;">QTY RENCANA</th>
                                            <th class="text-success" style="width: 15%;">QTY DITERIMA</th>
                                            <th class="text-danger" style="width: 15%;">QTY WASTE</th>
                                            <th style="width: 13%;">SATUAN</th>
                                        </tr>
                                    </thead>
                                    <tbody id="isi-item-pb">
                                        <tr><td colspan="6" class="text-center text-muted py-4">Silakan pilih Kode Pembelian (PB) di atas terlebih dahulu</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modalUploadWaste" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border-radius: 12px;">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title fw-bold text-dark"><i class="fa fa-camera me-2"></i>Upload Bukti Waste</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" id="modal-body-waste">
                                </div>
                                <div class="modal-footer border-0 bg-light rounded-bottom">
                                    <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-success btn-round fw-bold px-4">Konfirmasi & Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#id_pembelian').change(function() {
            var idPembelian = $(this).val();
            if(idPembelian != "") {
                $.ajax({
                    url: 'get_pb_detail.php',
                    type: 'POST',
                    data: {id: idPembelian},
                    success: function(data) {
                        $('#isi-item-pb').html(data);
                    }
                });
            } else {
                $('#isi-item-pb').html('<tr><td colspan="6" class="text-center text-muted py-4">Silakan pilih Kode Pembelian (PB) terlebih dahulu</td></tr>');
            }
        });

        // UPDATE LOGIKA JS: Hitung otomatis & kembalikan ke Qty Rencana jika Waste 0
        $(document).on('input', '.input-qty-rusak', function() {
            let row = $(this).closest('tr');
            let maxQty = parseFloat(row.find('.max-qty').val()) || 0;
            let inputTerima = row.find('.input-qty');
            let qtyRusak = parseFloat($(this).val()) || 0;
            
            // Cegah minus
            if (qtyRusak < 0) {
                $(this).val(0);
                qtyRusak = 0;
            }
            
            // Hitung sisa (Rencana dikurangi Waste)
            let hasilOke = maxQty - qtyRusak;
            if(hasilOke < 0) hasilOke = 0; 
            
            // Set value Qty Diterima langsung sesuai perhitungannya
            inputTerima.val(hasilOke % 1 !== 0 ? hasilOke.toFixed(2) : hasilOke);
            
            // Kunci jika ada waste, buka kuncinya kalau 0
            if (qtyRusak > 0) {
                inputTerima.prop('readonly', true);
            } else {
                inputTerima.prop('readonly', false);
            }
        });

        // Modal Upload Foto saat Simpan
        $('#btn-trigger-simpan').click(function(e) {
            e.preventDefault();
            
            if(!$('#formPenerimaan')[0].checkValidity()) {
                $('#formPenerimaan')[0].reportValidity();
                return;
            }

            let adaWaste = false;
            let htmlModal = '';

            $('#isi-item-pb .item-row').each(function(index) {
                let qtyRusak = parseFloat($(this).find('.input-qty-rusak').val()) || 0;
                
                if (qtyRusak > 0) {
                    adaWaste = true;
                    let namaBb = $(this).find('.nama-bb-td').text().trim();
                    
                    htmlModal += `
                        <div class="form-group mb-3 border-bottom pb-3">
                            <label class="fw-bold mb-1" style="font-size: 13px;">Lampirkan Foto: <span class="text-primary">${namaBb}</span></label>
                            <div class="text-danger small mb-2 fw-bold"><i class="fa fa-exclamation-triangle"></i> Jumlah Waste: ${qtyRusak}</div>
                            <input type="file" name="foto_waste[${index}]" class="form-control" accept="image/*" required>
                        </div>
                    `;
                }
            });

            if (adaWaste) {
                $('#modal-body-waste').html(htmlModal);
                $('#modalUploadWaste').modal('show');
            } else {
                $('#formPenerimaan').submit();
            }
        });
    });
</script>
</body>
</html>