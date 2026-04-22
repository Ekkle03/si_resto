<?php
session_start();
include("../config/koneksi_mysql.php");

// --- GENERATOR KODE PRODUKSI OTOMATIS (Reset per Bulan) ---
$bulan_tahun_prd = date('my'); // Format: 0426 (Bulan 04, Tahun 26)
$tgl_full_prd    = date('dmy'); // Format: 170426 (Tgl 17, Bulan 04, Tahun 26)

$sql_kodeprd = "SELECT MAX(CAST(RIGHT(kode_produksi, 2) AS UNSIGNED)) as max_urut 
                FROM produksi 
                WHERE kode_produksi LIKE 'PRD__{$bulan_tahun_prd}%'";

$q_kodeprd = mysqli_query($koneksi, $sql_kodeprd);
$d_kodeprd = mysqli_fetch_assoc($q_kodeprd);

// Ambil nomor urut terakhir, kalau kosong berarti 0
$noUrut_prd = $d_kodeprd['max_urut'] ?? 0;
$noUrut_prd++; // Tambah 1 untuk produksi baru

// Gabungkan PRD, tanggal hari ini, dan urutan bulanan (format 2 digit)
$kode_produksi_otomatis = "PRD" . $tgl_full_prd . sprintf("%02s", $noUrut_prd);
// ----------------------------------------------------------

// 1. Query Riwayat: Ditambahkan p.kode_produksi
$daftar_produksi = [];
$sql_history = "
    SELECT 
        p.id_produksi, p.kode_produksi, p.tgl_produksi, p.qty_rencana, p.qty_realisasi, p.status,
        bsj.nama_bsj AS nama_produk, sat.nama_satuan, p.id_bsj
    FROM produksi p
    JOIN master_bahan_setengah_jadi bsj ON p.id_bsj = bsj.id_bsj
    JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
    WHERE bsj.id_kategori = 19
    ORDER BY p.tgl_produksi DESC
";
$q = mysqli_query($koneksi, $sql_history);
if ($q) while ($r = mysqli_fetch_assoc($q)) $daftar_produksi[] = $r;

// 2. Query Dropdown: Hanya produk kategori 19 yang sudah punya resep (BOM)
$item_produksi_list = [];
$sql_dropdown = "
    SELECT DISTINCT bsj.id_bsj, bsj.nama_bsj 
    FROM master_bahan_setengah_jadi bsj
    JOIN master_bom bom ON bsj.id_bsj = bom.id_induk AND bom.tipe_bom = 'BSJ'
    WHERE bsj.id_kategori = 19 
    ORDER BY bsj.nama_bsj ASC
";
$q_item = mysqli_query($koneksi, $sql_dropdown);
if ($q_item) while ($r = mysqli_fetch_assoc($q_item)) $item_produksi_list[] = $r;

$pesan = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

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
    <title>Produksi Langsung</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

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

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .btn-outline-primary-thicker { border-width: 2px !important; font-weight: 500 !important; }
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
                <div class="page-header"><h3 class="fw-bold mb-3">Produksi Olahan Dasar (BSJ)</h3></div>

                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center">
                        <h4 class="card-title">Daftar Produksi</h4>
                        <button class="btn btn-primary btn-round ms-auto fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
                            <i class="fa fa-plus"></i> Tambah Produksi
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if ($pesan): ?><div id="auto-alert" class="alert alert-info auto-close"><i class="fa fa-info-circle me-1"></i> <?= htmlspecialchars($pesan) ?></div><?php endif; ?>
                        <div class="table-responsive">
                            <table id="tabel-langsung" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width: 50px;">NO</th>
                                        <th>KODE</th>
                                        <th style="width: 100px;">TANGGAL</th>
                                        <th>PRODUK</th>
                                        <th>RENCANA</th>
                                        <th>REALISASI</th>
                                        <th>STATUS</th>
                                        <th style="width: 120px;">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach($daftar_produksi as $p): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $no++ ?></td>
                                        <td class="text-center fw-bold text-dark"><?= $p['kode_produksi'] ?? '-' ?></td>
                                        <td class="text-center"><?= date('d/m/Y', strtotime($p['tgl_produksi'])) ?></td>
                                        <td class="text-start fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                        <td class="text-center fw-bold text-primary"><?= (float)$p['qty_rencana'] ?> <?= $p['nama_satuan'] ?></td>
                                        <td class="text-center"><?= $p['status'] == 'Selesai' ? (float)$p['qty_realisasi']." ".$p['nama_satuan'] : '-' ?></td>
                                        <td class="text-center"><span class="badge <?= $p['status']=='Rencana'?'bg-warning text-dark':'bg-success' ?> shadow-sm"><?= $p['status'] ?></span></td>
                                        <td class="text-center">
                                            <div class="form-button-action justify-content-center">
                                                <button type="button" class="btn btn-link btn-primary p-1 btn-detail" 
                                                        data-id="<?= $p['id_produksi'] ?>"
                                                        data-nama="<?= htmlspecialchars($p['nama_produk']) ?>"
                                                        data-status="<?= $p['status'] ?>"
                                                        data-hasil="<?= (float)$p['qty_realisasi'] ?> <?= $p['nama_satuan'] ?>"
                                                        title="Lihat Rincian Bahan">
                                                    <i class="fa fa-eye"></i>
                                                </button>

                                                <?php if($p['status'] == 'Rencana'): ?>
                                                    <button type="button" class="btn btn-link btn-success p-1 btn-input-hasil" 
                                                            data-id="<?= $p['id_produksi'] ?>" 
                                                            data-rencana="<?= (float)$p['qty_rencana'] ?>"
                                                            data-satuan="<?= $p['nama_satuan'] ?>"
                                                            data-nama="<?= htmlspecialchars($p['nama_produk']) ?>"
                                                            title="Selesaikan Produksi">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-link btn-danger p-1" 
                                                            onclick="confirmDelete(<?= $p['id_produksi'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')" 
                                                            title="Hapus Rencana">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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

<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <form method="POST" action="add_produksi_langsung.php">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white fw-bold"><i class="fa fa-plus me-2"></i>Jadwalkan Produksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    
                    <div class="form-group mb-3">
                        <label class="fw-bold mb-1">Kode Produksi</label>
                        <input type="text" name="kode_produksi" class="form-control fw-bold bg-light text-primary" value="<?= $kode_produksi_otomatis ?>" readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-bold mb-1">Pilih Produk</label>
                        <select name="id_bsj" id="select_produk" class="form-select" required>
                            <option value="">-- Pilih Item --</option>
                            <?php foreach ($item_produksi_list as $item): ?>
                                <option value="<?= $item['id_bsj'] ?>"><?= htmlspecialchars($item['nama_bsj']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold mb-1">Jumlah BOM</label>
                        <div class="input-group">
                            <input type="number" step="1" class="form-control" name="qty_bom_input" id="qty_bom_input" required placeholder="0">
                            <span class="input-group-text bg-light fw-bold">BOM</span>
                        </div>
                        <div class="mt-3 p-3 bg-light border rounded shadow-sm" id="box_info_langsung" style="display:none;">
                            <small class="text-muted d-block" id="info_yield"></small>
                            <p class="mb-1 fw-bold text-primary" id="total_porsi_estimasi"></p>
                            <div id="status_stok_live"></div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-1">
                        <label class="fw-bold mb-1">Tanggal Produksi</label>
                        <input type="date" class="form-control" name="tgl_produksi" id="tgl_input" required>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary btn-round px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSimpan" class="btn btn-primary btn-round px-4">Simpan Rencana</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSelesai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <form method="POST" action="proses_produksi_langsung.php" enctype="multipart/form-data">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white fw-bold"><i class="fa fa-check-circle me-2"></i>Konfirmasi Hasil Produksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_produksi" id="finish_id">
                    <input type="hidden" id="f_max_rencana">
                    
                    <div class="alert alert-warning text-dark border-0 shadow-sm mb-4">
                        <i class="fa fa-info-circle me-1"></i> Selesaikan produksi Olahan Dasar:<br>
                        <strong class="fs-5" id="finish_nama"></strong>
                        <small class="d-block mt-1">Rencana Awal: <span id="label_rencana" class="fw-bold text-dark"></span></small>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 form-group p-0 pe-2">
                            <label class="fw-bold mb-1">Hasil Oke (<span id="label_satuan_finish"></span>)</label>
                            <input type="number" step="0.01" class="form-control border-success fs-5 fw-bold text-success" name="qty_realisasi" id="input_realisasi" required>
                        </div>
                        <div class="col-6 form-group p-0 ps-2">
                            <label class="fw-bold mb-1 text-danger">Waste/Gagal (<span id="label_satuan_waste"></span>)</label>
                            <input type="number" step="0.01" class="form-control border-danger fs-5 fw-bold text-danger" name="qty_waste" id="input_waste" value="0" required>
                        </div>
                    </div>

                    <div class="form-group p-0 mt-3" id="box_foto_waste_langsung" style="display:none;">
                        <label class="fw-bold mb-1">Foto Bukti Waste</label>
                        <input type="file" name="foto_waste" id="input_foto_waste" class="form-control" accept="image/*">
                        <small class="text-danger mt-1">* Wajib dilampirkan jika ada hasil yang gagal/terbuang.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-round px-4 fw-bold">Selesaikan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white fw-bold"><i class="fa fa-list me-2"></i> Rincian Bahan Produksi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3 bg-light rounded p-3 mx-0 border">
                    <div class="col-md-4 border-end text-center">
                        <p class="mb-0 text-muted small fw-bold text-uppercase">Produk Jadi</p>
                        <h5 id="det_nama" class="fw-bold text-dark mb-0 mt-1"></h5>
                    </div>
                    <div class="col-md-4 border-end text-center">
                        <p class="mb-0 text-muted small fw-bold text-uppercase">Hasil Jadi</p>
                        <h5 id="det_hasil" class="fw-bold text-success mb-0 mt-1">-</h5>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="mb-0 text-muted small fw-bold text-uppercase">Status Produksi</p>
                        <h5 id="det_status" class="fw-bold text-primary mb-0 mt-1"></h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 50px;">NO</th>
                                <th>NAMA BAHAN</th>
                                <th style="width: 150px;">QTY PEMAKAIAN</th>
                                <th style="width: 120px;">SATUAN</th>
                            </tr>
                        </thead>
                        <tbody id="det_bahan_body">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom">
                <button type="button" class="btn btn-secondary btn-round px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#tabel-langsung').DataTable({ 
            "ordering": false 
        });
        
        // Set tanggal input otomatis ke hari ini (DITAMBAHKAN)
        document.getElementById('tgl_input').valueAsDate = new Date();

        // Notifikasi auto-close
        window.setTimeout(function() {
            $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 3000);

        var target_per_bom = 0;
        var nama_satuan = "";
        var timer;

        // 1. AJAX Ambil Info Target
        $('#select_produk').change(function() {
            var id = $(this).val();
            if (id != "") {
                $('#box_info_langsung').show();
                $.post('get_satuan_dan_target.php', {id_bsj: id}, function(res) {
                    var data = JSON.parse(res);
                    target_per_bom = parseFloat(data.target);
                    nama_satuan = data.satuan;
                    $('#info_yield').html('<i class="fa fa-info-circle"></i> Info Resep: 1 BOM = <b>' + target_per_bom + ' ' + nama_satuan + '</b>');
                    cekStokRealTime();
                });
            } else {
                $('#box_info_langsung').hide();
            }
        });

        $('#qty_bom_input').on('input', function() { cekStokRealTime(); });

        function cekStokRealTime() {
            var id_bsj = $('#select_produk').val();
            var qty_bom = parseFloat($('#qty_bom_input').val()) || 0;
            var btn = $('#btnSimpan');

            if (qty_bom > 0 && target_per_bom > 0) {
                var total = (qty_bom * target_per_bom).toFixed(2);
                $('#total_porsi_estimasi').html('<i class="fa fa-calculator me-1"></i> Estimasi Hasil: <b>' + parseFloat(total) + ' ' + nama_satuan + '</b>');

                clearTimeout(timer);
                $('#status_stok_live').html('<div class="mt-2"><span class="spinner-border spinner-border-sm text-primary"></span> <small class="text-muted">Mengecek ketersediaan bahan...</small></div>');
                timer = setTimeout(function() {
                    // Pakai id_gudang 2 untuk operasional
                    $.post('cek_stok_produksi.php', {id_bsj: id_bsj, qty_bom: qty_bom, id_gudang: 2}, function(res) {
                        var data = JSON.parse(res);
                        if (data.status == 'error') {
                            $('#status_stok_live').html('<div class="alert alert-danger mt-2 py-2 small mb-0 border-0"><i class="fa fa-exclamation-circle me-1"></i> '+data.message+'</div>');
                            btn.prop('disabled', true);
                        } else {
                            $('#status_stok_live').html('<div class="text-success small fw-bold mt-2"><i class="fa fa-check-circle me-1"></i> Stok Aman di Gudang Operasional</div>');
                            btn.prop('disabled', false);
                        }
                    });
                }, 400);
            }
        }

        // 2. Modal Selesaikan
        $('.btn-input-hasil').on('click', function() {
            let id = $(this).data('id');
            let nama = $(this).data('nama');
            let rencana = parseFloat($(this).data('rencana'));
            let satuan = $(this).data('satuan');

            $('#finish_id').val(id);
            $('#finish_nama').text(nama);
            $('#label_rencana').text(rencana + ' ' + satuan);
            $('#label_satuan_finish').text(satuan);
            $('#label_satuan_waste').text(satuan);
            $('#f_max_rencana').val(rencana);
            
            // Reset
            $('#input_realisasi').val(rencana).prop('readonly', false);
            $('#input_waste').val(0);
            $('#box_foto_waste_langsung').hide();
            $('#input_foto_waste').prop('required', false);

            $('#modalSelesai').modal('show');
        });

        // 2b. Logika Waste Otomatis (DIPERBAIKI)
        $('#input_waste').on('input', function() {
            let maxQty = parseFloat($('#f_max_rencana').val()) || 0;
            let waste = parseFloat($(this).val()) || 0;
            
            // Cegah angka minus
            if(waste < 0) { 
                $(this).val(0); 
                waste = 0; 
            }
            
            // CEGAH WASTE LEBIH BESAR DARI RENCANA
            if(waste > maxQty) {
                // Tampilkan alert (opsional, tapi bagus biar user tahu kenapa dibalikin)
                alert("Jumlah Waste tidak boleh lebih besar dari Rencana Awal (" + maxQty + ")!");
                
                // Kembalikan nilai waste ke batas maksimal
                $(this).val(maxQty);
                waste = maxQty;
            }
            
            if(waste > 0) {
                let hasilOke = maxQty - waste;
                
                $('#input_realisasi').val(hasilOke % 1 !== 0 ? hasilOke.toFixed(2) : hasilOke).prop('readonly', true);
                $('#box_foto_waste_langsung').slideDown();
                $('#input_foto_waste').prop('required', true); 
            } else {
                $('#input_realisasi').val(maxQty).prop('readonly', false);
                $('#box_foto_waste_langsung').slideUp();
                $('#input_foto_waste').prop('required', false);
            }
        });

        // 3. Modal Detail Mata
        $(document).on('click', '.btn-detail', function() {
            var id = $(this).data('id');
            $('#det_nama').text($(this).data('nama'));
            $('#det_hasil').text('-'); 
            
            let status = $(this).data('status');
            if(status === 'Rencana') {
                $('#det_status').html('<span class="badge bg-warning text-dark">Masih Rencana</span>');
            } else if (status === 'Selesai') {
                $('#det_status').html('<span class="badge bg-success">Telah Selesai</span>');
            } else {
                $('#det_status').html('<span class="badge bg-danger">Batal</span>');
            }

            $('#det_bahan_body').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Memuat data...</td></tr>');
            $('#modalDetail').modal('show');

            $.post('get_produksi_detail.php', {id_produksi: id}, function(res) {
                let dataParsed;
                try {
                    dataParsed = (typeof res === 'object') ? res : JSON.parse(res);
                } catch (e) {
                    $('#det_bahan_body').html('<tr><td colspan="4" class="text-center text-danger py-3">Gagal memproses data dari server.</td></tr>');
                    return;
                }

                if(dataParsed.status === 'error') {
                    $('#det_bahan_body').html('<tr><td colspan="4" class="text-center text-danger py-3">'+dataParsed.msg+'</td></tr>');
                    return;
                }

                $('#det_hasil').text(dataParsed.hasil_jadi || '-');

                var html = '';
                var no = 1;
                let dataBahan = dataParsed.bahan;

                if(Array.isArray(dataBahan) && dataBahan.length > 0) {
                    dataBahan.forEach(function(item) {
                        html += '<tr>' +
                                '<td class="text-center text-muted">' + (no++) + '</td>' +
                                '<td class="fw-bold text-dark">' + item.nama + '</td>' +
                                '<td class="text-center fw-bold text-primary">' + item.qty + '</td>' +
                                '<td class="text-center text-muted">' + item.satuan + '</td>' +
                                '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada rincian bahan untuk produksi ini.</td></tr>';
                }
                $('#det_bahan_body').html(html);
            });
        });
    });

    // Konfirmasi Hapus Data (Diarahkan ke delete_produksi.php dengan parameter jenis=2)
    function confirmDelete(id, nama) {
        Swal.fire({
            title: 'Hapus Rencana Produksi?',
            text: "Rencana produksi " + nama + " akan dibatalkan dan dihapus.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_produksi.php?id=' + id + '&jenis=2';
            }
        })
    }
</script>
</body>
</html>