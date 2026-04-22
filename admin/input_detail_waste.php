<?php
session_start();
include("../config/koneksi_mysql.php");

$id_header = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');

$sql_header = "SELECT h.*, g.nama_gudang, k.nama_lengkap 
               FROM header_waste h
               JOIN master_gudang g ON h.id_gudang = g.id_gudang
               JOIN master_karyawan k ON h.id_karyawan = k.id_karyawan
               WHERE h.id_header_waste = '$id_header'";
$q_header = mysqli_query($koneksi, $sql_header);
$d_header = mysqli_fetch_assoc($q_header);

if (!$d_header) {
    header("Location: waste.php");
    exit();
}

// Navbar Session
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
    <title>Waste</title>
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

    <style>
        .card-header-info { background: #fff; border-bottom: 1px solid #f0f0f5; padding: 15px 20px; }
        .label-header { font-size: 12px; color: #8d9498; text-transform: uppercase; margin-bottom: 2px; }
        .val-header { font-size: 15px; font-weight: 500; color: #1a2035; }
        .table-detail thead th { background: #f8f9fb; font-size: 11px; font-weight: 700; color: #8d9498; text-transform: uppercase; border: none; padding: 12px; }
        .highlight-input { border: 1.5px solid #dc3545 !important; }
        
        /* CSS Popup Stok Melayang */
        .input-wrapper { position: relative; }
        .stock-info-popup {
            font-size: 10px;
            font-weight: 800;
            color: #ffffff;
            background: #1572e8;
            padding: 2px 10px;
            border-radius: 4px;
            position: absolute;
            top: -22px;
            left: 5px;
            display: none;
            z-index: 99;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
        .stock-info-popup::after {
            content: "";
            position: absolute;
            bottom: -4px;
            left: 10px;
            border-width: 4px 4px 0;
            border-style: solid;
            border-color: #1572e8 transparent;
        }

        .select2-container .select2-selection--single { height: 40px !important; display: flex; align-items: center; border: 1px solid #ebedef; }
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
            <!-- ── END NAVBAR ─────────────────────────────────────────────── -->

            <div class="container">
            <div class="page-inner">
                
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-1">Input Item Waste</h3>
                        <span class="text-muted">Nota: <?= $d_header['kode_waste'] ?> | Tgl: <?= date('d/m/Y', strtotime($d_header['tgl_waste'])) ?></span>
                    </div>
                    <div class="d-flex" style="gap: 10px;">
                        <button type="submit" form="formWaste" class="btn btn-success fw-bold shadow-sm" style="border-radius: 4px;">
                            <i class="fas fa-save me-1"></i> SIMPAN
                        </button>
                        <a href="#" class="btn btn-danger fw-bold shadow-sm btn-batal" style="border-radius: 4px;">
                            BATAL
                        </a>
                    </div>
                </div>
                <div class="card card-round mb-4 border-0 shadow-sm">
                    <div class="card-header-info">
                        <div class="row text-center">
                            <div class="col-md-4 border-end">
                                <div class="label-header"><b>Gudang Asal</b></div>
                                <div class="val-header"><?= $d_header['nama_gudang'] ?></div>
                            </div>
                            <div class="col-md-4 border-end">
                                <div class="label-header"><b>Pencatat</b></div>
                                <div class="val-header"><?= $d_header['nama_lengkap'] ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="label-header"><b>Status</b></div>
                                <div class="val-header text-warning fw-bold">Draft</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formWaste" action="proses_detail_waste.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_header_waste" id="id_header_waste_val" value="<?= $id_header ?>">
                    <input type="hidden" id="id_gudang_val" value="<?= $d_header['id_gudang'] ?>">

                    <div class="card card-round shadow-sm border-0">
                        <div class="card-header bg-white">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title fw-bold" style="font-size: 16px;">Daftar Item Waste</h4>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-detail mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 25%">Nama Barang</th>
                                        <th style="width: 15%">Alasan</th>
                                        <th style="width: 12%">Qty</th>
                                        <th style="width: 10%">Satuan</th>
                                        <th style="width: 15%">Keterangan</th>
                                        <th style="width: 18%">Foto Bukti</th>
                                        <th style="width: 5%">#</th>
                                    </tr>
                                </thead>
                                <tbody id="row_container"></tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-light p-3">
                            <button type="button" class="btn btn-primary btn-round btn-sm" id="btn_tambah_baris">
                                <i class="fa fa-plus me-1"></i> Tambah Baris Baru
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    let rowCount = 0;
    const gudangID = $('#id_gudang_val').val();

    $('#btn_tambah_baris').click(function() {
        rowCount++;
        let html = `
        <tr id="row_${rowCount}">
            <td class="input-wrapper">
                <div id="popup_${rowCount}" class="stock-info-popup"></div>
                <select name="items[${rowCount}][id_item]" class="form-control select_item" required style="width: 100%"></select>
                <input type="hidden" id="max_stok_konv_${rowCount}" value="0">
            </td>
            <td>
                <select name="items[${rowCount}][alasan]" class="form-select select_alasan" required>
                    <option value="Busuk/Rusak">Busuk / Rusak</option>
                    <option value="Expired">Kadaluwarsa</option>
                    <option value="Gagal Produksi">Gagal Produksi</option>
                    <option value="Lainnya">Lainnya...</option>
                </select>
            </td>
            <td><input type="number" step="any" id="qty_${rowCount}" name="items[${rowCount}][qty]" class="form-control input-qty" placeholder="0" required></td>
            <td class="align-middle text-center"><span id="label_satuan_${rowCount}" class="fw-bold text-dark" style="font-size: 13px;">-</span></td>
            <td><input type="text" name="items[${rowCount}][keterangan_item]" class="form-control" placeholder="Catatan..."></td>
            <td><input type="file" name="foto_${rowCount}" class="form-control form-control-sm" accept="image/*" required></td>
            <td class="text-center"><button type="button" class="btn btn-link btn-danger remove_row"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        
        $('#row_container').append(html);

        let selectEl = $(`#row_${rowCount} .select_item`);
        let currentPopup = $(`#popup_${rowCount}`);
        let currentMaxKonv = $(`#max_stok_konv_${rowCount}`);
        let currentLabelSat = $(`#label_satuan_${rowCount}`);

        selectEl.select2({
            placeholder: "Cari...",
            ajax: {
                url: 'get_bahan_ajax.php',
                dataType: 'json',
                data: function(params) { return { q: params.term, id_gudang: gudangID }; },
                processResults: function(data) { return { results: data }; }
            },
            escapeMarkup: function(m) { return m; },
            templateResult: function(repo) {
                if (repo.loading) return repo.text;
                return `<div class="d-flex justify-content-between"><span>${repo.text}</span> <small class="text-muted" style="font-size:9px">Cek Stok</small></div>`;
            }
        }).on('select2:select', function (e) {
            let data = e.params.data;
            let displayStok, satuan;

            if (gudangID == "1") {
                displayStok = (parseFloat(data.stok) / parseFloat(data.konv)).toFixed(2); 
                satuan = data.sat_b;
            } else {
                displayStok = data.stok;
                satuan = data.sat_k;
            }

            currentMaxKonv.val(displayStok);
            currentLabelSat.text(satuan);

            currentPopup.html(`<i class="fas fa-box-open me-1"></i> Sisa: ${displayStok} ${satuan}`).fadeIn();
            setTimeout(() => { currentPopup.fadeOut(); }, 3000); 
        });
    });

    $(document).on('change', '.input-qty', function() {
        let rowId = $(this).attr('id').split('_')[1];
        let inputVal = parseFloat($(this).val());
        let maxStok = parseFloat($(`#max_stok_konv_${rowId}`).val());
        let unit = $(`#label_satuan_${rowId}`).text();
        
        if (inputVal > maxStok) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Tidak Cukup!',
                text: 'Sisa stok hanya ' + maxStok + ' ' + unit
            });
            $(this).val(maxStok).focus();
        }
    });

    $(document).on('change', '.select_alasan', function() {
        let row = $(this).closest('tr');
        let ketInput = row.find('input[name*="keterangan_item"]');
        if($(this).val() === 'Lainnya') {
            ketInput.addClass('highlight-input').prop('required', true).focus();
        } else {
            ketInput.removeClass('highlight-input').prop('required', false);
        }
    });

    $('#btn_tambah_baris').click();
    
    $(document).on('click', '.remove_row', function() {
        if($('#row_container tr').length > 1) { $(this).closest('tr').remove(); }
    });

    $('.btn-batal').click(function(e) {
        e.preventDefault();
        let idHeader = $('#id_header_waste_val').val();
        
        Swal.fire({
            title: 'Batalkan Transaksi?',
            text: "Data nota ini akan dihapus dari sistem.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "delete_waste.php?id=" + idHeader;
            }
        });
    });
});
</script>
</body>
</html>