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

// 1. Query Riwayat: Filter agar produk 'Olahan Dasar' (kategori 19) tidak muncul di sini
$daftar_produksi = [];
$sql = "SELECT p.id_produksi, p.kode_produksi, p.tgl_produksi, p.qty_rencana, p.status, p.qty_realisasi,
               bsj.nama_bsj AS nama_produk, bsj.tahap, sat.nama_satuan, p.id_bsj
        FROM produksi p
        JOIN master_bahan_setengah_jadi bsj ON p.id_bsj = bsj.id_bsj
        JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
        WHERE bsj.id_kategori != 19
        ORDER BY p.tgl_produksi DESC, p.id_produksi DESC";
$q = mysqli_query($koneksi, $sql);
if ($q) while ($r = mysqli_fetch_assoc($q)) $daftar_produksi[] = $r;

// 2. Query Dropdown: Filter hanya BSJ yang sudah memiliki resep di Master BOM
$item_produksi_list = [];
$sql_dropdown = "SELECT DISTINCT bsj.id_bsj, bsj.nama_bsj, bsj.tahap 
                 FROM master_bahan_setengah_jadi bsj
                 INNER JOIN master_bom bom ON bsj.id_bsj = bom.id_induk AND bom.tipe_bom = 'BSJ'
                 WHERE bsj.id_kategori != 19 
                 ORDER BY bsj.tahap ASC, bsj.nama_bsj ASC";
$q_item = mysqli_query($koneksi, $sql_dropdown);
if ($q_item) while ($r = mysqli_fetch_assoc($q_item)) $item_produksi_list[] = $r;

// Variabel Navbar menggunakan session
$nama = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username'] ?? 'guest');
$role = htmlspecialchars($_SESSION['nama_role'] ?? '');
$foto = !empty($_SESSION['foto_profil']) 
        ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) 
        : 'assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Produksi 1 - Sistem Resto</title>
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
                    <a href="dashboard.php" class="logo"><img src="assets/img/logo/logo_resto.png" alt="Logo Resto" class="navbar-brand" height="30" /></a>
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
                                <div class="avatar-sm"><img src="<?= $foto ?>" alt="..." class="avatar-img rounded-circle" onerror="this.src='assets/img/profil/default.png'"/></div>
                                <span class="profile-username"><span class="op-7">Selamat Datang,</span> <span class="fw-bold"><?= $nama ?></span></span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg"><img src="<?= $foto ?>" alt="image profile" class="avatar-img rounded" onerror="this.src='assets/img/profil/default.png'"/></div>
                                            <div class="u-text">
                                                <h4><?= $nama ?></h4>
                                                <p class="text-muted">@<?= $username ?></p>
                                                <?php if (!empty($role)): ?><span class="badge bg-secondary mb-2"><?= $role ?></span><?php endif; ?>
                                                <br><a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li><div class="dropdown-divider"></div><a class="dropdown-item" href="#">Pengaturan Akun</a><div class="dropdown-divider"></div><a class="dropdown-item" href="../logout.php">Logout</a></li>
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
                    <h3 class="fw-bold mb-0">Produksi Berjenjang (Tahap 1 & 2)</h3>
                    <div>
                        <button class="btn btn-primary btn-round fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addProduksiModal">
                            <i class="fa fa-plus me-1"></i> Tambah Produksi
                        </button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header bg-white d-flex align-items-center py-3">
                                <h4 class="card-title fw-bold" style="font-size: 15px !important;">Daftar Produksi</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['flash_msg'])): ?>
                                    <div class="alert alert-info border-0 shadow-sm auto-close">
                                        <i class="fa fa-info-circle me-1"></i> <?= $_SESSION['flash_msg'] ?>
                                    </div>
                                    <?php unset($_SESSION['flash_msg']); ?>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="tabel-produksi" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                        <thead class="bg-light text-center">
                                            <tr>
                                                <th style="width: 50px;">NO</th>
                                                <th>KODE</th>
                                                <th style="width: 100px;">TGL PRODUKSI</th>
                                                <th>NAMA PRODUK</th>
                                                <th>TAHAP</th>
                                                <th>RENCANA</th>
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
                                                <td class="text-center"><span class="badge badge-secondary shadow-sm"><?= strtoupper($p['tahap']) ?></span></td>
                                                <td class="text-center fw-bold text-primary"><?= (float)$p['qty_rencana'] ?> <?= $p['nama_satuan'] ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= $p['status']=='Rencana'?'bg-warning text-dark':($p['status']=='Batal'?'bg-danger':'bg-success') ?> shadow-sm"><?= $p['status'] ?></span>
                                                </td>
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
                                                            <button type="button" class="btn btn-link btn-success p-1 btn-finish" 
                                                                    data-id="<?= $p['id_produksi'] ?>" 
                                                                    data-nama="<?= htmlspecialchars($p['nama_produk']) ?>"
                                                                    data-rencana="<?= (float)$p['qty_rencana'] ?>"
                                                                    data-satuan="<?= $p['nama_satuan'] ?>"
                                                                    title="Selesaikan Produksi">
                                                                <i class="fa fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-link btn-danger p-1" onclick="confirmDelete(<?= $p['id_produksi'] ?>, '<?= htmlspecialchars($p['nama_produk']) ?>')" title="Hapus Rencana">
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
    </div>
</div>

<div class="modal fade" id="addProduksiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <form method="POST" action="add_produksi.php" id="formAddProduksi">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white fw-bold"><i class="fa fa-plus me-2"></i>Buat Produksi Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3 text-start">
                        <label class="fw-bold mb-1">Kode Produksi</label>
                        <input type="text" name="kode_produksi" class="form-control fw-bold bg-light text-primary" value="<?= $kode_produksi_otomatis ?>" readonly>
                    </div>

                    <div class="form-group mb-3 text-start">
                        <label class="fw-bold mb-1">Pilih Hasil Produksi</label>
                        <select name="id_bsj" id="select_produk_berjenjang" class="form-select" required>
                            <option value="">-- Pilih BSJ 1 / BSJ 2 --</option>
                            <?php foreach ($item_produksi_list as $item): ?>
                                <option value="<?= $item['id_bsj'] ?>">[<?= strtoupper($item['tahap']) ?>] <?= htmlspecialchars($item['nama_bsj']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3 text-start">
                        <label class="fw-bold mb-1">Jumlah BOM (Resep)</label>
                        <div class="input-group">
                            <input type="number" step="1" class="form-control" name="qty_bom_input" id="qty_bom_input" required placeholder="0">
                            <span class="input-group-text bg-light fw-bold">BOM</span>
                        </div>
                        <div class="mt-3 p-3 bg-light border rounded shadow-sm" id="box_info_produksi" style="display:none;">
                            <small class="text-muted d-block" id="info_yield_berjenjang"></small>
                            <p class="mb-1 fw-bold text-primary" id="total_porsi_estimasi"></p>
                            <div id="status_stok_live"></div> 
                        </div>
                    </div>
                    <div class="form-group text-start">
                        <label class="fw-bold mb-1">Tanggal Produksi</label>
                        <input type="date" class="form-control" name="tgl_produksi" id="tgl_input" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSimpan" class="btn btn-primary btn-round px-4">Simpan Rencana</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmFinish" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <form method="POST" action="proses_produksi_selesai.php" enctype="multipart/form-data">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white fw-bold"><i class="fa fa-check-circle me-2"></i>Konfirmasi Hasil Produksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_produksi" id="f_id">
                    <input type="hidden" id="f_max_rencana">
                    
                    <div class="alert alert-warning text-dark border-0 shadow-sm mb-4">
                        <i class="fa fa-info-circle me-1"></i> Anda akan menyelesaikan produksi:<br>
                        <strong class="fs-5" id="f_nama"></strong>
                        <small class="d-block mt-1">Rencana Awal: <span id="f_rencana" class="fw-bold text-dark"></span></small>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 form-group p-0 pe-2">
                            <label class="fw-bold mb-1">Hasil Oke (<span class="f_satuan"></span>)</label>
                            <input type="number" step="0.01" class="form-control border-success fs-5 fw-bold text-success" name="qty_realisasi" id="f_realisasi" required>
                        </div>
                        <div class="col-6 form-group p-0 ps-2">
                            <label class="fw-bold mb-1 text-danger">Waste/Gagal (<span class="f_satuan"></span>)</label>
                            <input type="number" step="0.01" class="form-control border-danger fs-5 fw-bold text-danger" name="qty_waste" id="f_waste" value="0" required>
                        </div>
                    </div>

                    <div class="form-group p-0 mt-3" id="box_foto_waste" style="display:none;">
                        <label class="fw-bold mb-1">Foto Bukti Waste</label>
                        <input type="file" name="foto_waste" id="f_foto_waste" class="form-control" accept="image/*">
                        <small class="text-danger mt-1">* Wajib dilampirkan jika ada hasil produksi yang gagal/tumpah.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom">
                    <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-round px-4 fw-bold">Simpan</button>
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
        // Inisialisasi DataTable tanpa sorting otomatis agar data terbaru tetap di atas
        $('#tabel-produksi').DataTable({ 
            "ordering": false 
        });
        
        // Set tanggal input otomatis ke hari ini
        document.getElementById('tgl_input').valueAsDate = new Date();

        // --- MODAL SELESAI PRODUKSI ---
        $(document).on('click', '.btn-finish', function() {
            $('#f_id').val($(this).data('id'));
            $('#f_nama').text($(this).data('nama'));
            
            let rencana = parseFloat($(this).data('rencana'));
            $('#f_rencana').text(rencana + ' ' + $(this).data('satuan'));
            $('.f_satuan').text($(this).data('satuan'));
            $('#f_max_rencana').val(rencana);
            
            // Reset form ke kondisi awal (Lunas/Oke semua)
            $('#f_realisasi').val(rencana).prop('readonly', false);
            $('#f_waste').val(0);
            $('#box_foto_waste').hide();
            $('#f_foto_waste').prop('required', false);

            $('#modalConfirmFinish').modal('show');
        });

        // Logika Otomatis: Jika Waste diisi, Hasil Oke berkurang sendiri
        $('#f_waste').on('input', function() {
            let maxQty = parseFloat($('#f_max_rencana').val()) || 0;
            let waste = parseFloat($(this).val()) || 0;
            
            if(waste < 0) { $(this).val(0); waste = 0; }
            
            if(waste > 0) {
                let hasilOke = maxQty - waste;
                if(hasilOke < 0) hasilOke = 0; 
                
                // Set hasil oke secara otomatis dan kunci inputnya agar tidak minus
                $('#f_realisasi').val(hasilOke % 1 !== 0 ? hasilOke.toFixed(2) : hasilOke).prop('readonly', true);
                $('#box_foto_waste').slideDown();
                $('#f_foto_waste').prop('required', true); 
            } else {
                // Jika waste 0, user bisa input hasil oke secara manual lagi
                $('#f_realisasi').val(maxQty).prop('readonly', false);
                $('#box_foto_waste').slideUp();
                $('#f_foto_waste').prop('required', false);
            }
        });

        // --- MODAL RINCIAN BAHAN (ANTI-MUTER) ---
        $(document).on('click', '.btn-detail', function() {
            var id = $(this).data('id');
            $('#det_nama').text($(this).data('nama'));
            $('#det_hasil').text('-'); // Reset teks hasil jadi
            
            let status = $(this).data('status');
            if(status === 'Rencana') {
                $('#det_status').html('<span class="badge bg-warning text-dark">Masih Rencana</span>');
            } else if (status === 'Selesai') {
                $('#det_status').html('<span class="badge bg-success">Telah Selesai</span>');
            } else {
                $('#det_status').html('<span class="badge bg-danger">Batal</span>');
            }

            // Tampilkan spinner saat memuat
            $('#det_bahan_body').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Memuat data...</td></tr>');
            $('#modalDetail').modal('show');

            $.post('get_produksi_detail.php', {id_produksi: id}, function(res) {
                // Penanganan JSON yang aman
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

                // Tampilkan Hasil Jadi jika ada (fallback ke '-')
                $('#det_hasil').text(dataParsed.hasil_jadi || '-');

                var html = '';
                var no = 1;
                let dataBahan = dataParsed.bahan;

                // Pastikan dataBahan adalah array yang valid
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

        // Notifikasi auto-close
        window.setTimeout(function() {
            $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 3000);

        var target_per_bom = 0;
        var nama_satuan = "";
        var timer;

        // --- LOGIKA TAMBAH PRODUKSI BARU ---
        $('#select_produk_berjenjang').change(function() {
            var id = $(this).val();
            if(id != "") {
                $('#box_info_produksi').show();
                $.post('get_satuan_dan_target.php', {id_bsj: id}, function(res) {
                    var data = JSON.parse(res);
                    target_per_bom = parseFloat(data.target);
                    nama_satuan = data.satuan;
                    $('#info_yield_berjenjang').html('<i class="fa fa-info-circle"></i> Info Resep: 1 BOM = <b>' + target_per_bom + ' ' + nama_satuan + '</b>');
                    hitungDanCekStok(id);
                });
            } else {
                $('#box_info_produksi').hide();
            }
        });

        $('#qty_bom_input').on('input', function() { hitungDanCekStok($('#select_produk_berjenjang').val()); });

        function hitungDanCekStok(id) {
            var qty_bom = parseFloat($('#qty_bom_input').val()) || 0;
            var btn = $('#btnSimpan');
            if(qty_bom > 0 && target_per_bom > 0) {
                var total = (qty_bom * target_per_bom).toFixed(2);
                $('#total_porsi_estimasi').html('<i class="fa fa-calculator me-1"></i> Estimasi Hasil: <b>' + parseFloat(total) + ' ' + nama_satuan + '</b>');
                
                clearTimeout(timer);
                $('#status_stok_live').html('<div class="mt-2"><span class="spinner-border spinner-border-sm text-primary"></span> <small class="text-muted">Mengecek ketersediaan bahan...</small></div>');
                
                timer = setTimeout(function() {
                    $.post('cek_stok_produksi.php', {id_bsj: id, qty_bom: qty_bom}, function(res) {
                        var d = JSON.parse(res);
                        if(d.status == 'error') {
                            $('#status_stok_live').html('<div class="alert alert-danger mt-2 py-2 small mb-0 border-0"><i class="fa fa-exclamation-circle me-1"></i> '+d.message+'</div>');
                            btn.prop('disabled', true);
                        } else {
                            $('#status_stok_live').html('<div class="text-success small fw-bold mt-2"><i class="fa fa-check-circle me-1"></i> Semua bahan baku tersedia di Gudang Produksi.</div>');
                            btn.prop('disabled', false);
                        }
                    });
                }, 400);
            }
        }
    });

    // Konfirmasi Hapus Data (Diarahkan ke delete_produksi.php dengan parameter jenis=1)
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
                // PARAMETER jenis=1 DITAMBAHKAN DI SINI
                window.location.href = 'delete_produksi.php?id=' + id + '&jenis=1';
            }
        })
    }
</script>
</body>
</html>