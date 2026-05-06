<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Navbar & Session
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$foto     = !empty($_SESSION['foto_profil']) ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) : 'assets/img/profil/default.png';

// 2. Tangkap ID Header
$id_header = $_GET['id_header'] ?? '';
if (empty($id_header)) { header("Location: stok_opname.php"); exit(); }

// 3. Ambil data Header & Gudang
$q_h = mysqli_query($koneksi, "SELECT h.*, g.nama_gudang 
                               FROM header_opname h 
                               JOIN master_gudang g ON h.id_gudang = g.id_gudang 
                               WHERE h.id_header_opname = '$id_header'");
$d_h = mysqli_fetch_assoc($q_h);
if (!$d_h) { header("Location: stok_opname.php"); exit(); }

$id_gudang   = $d_h['id_gudang'];
$nama_gudang = $d_h['nama_gudang'];
$kode_opn    = $d_h['kode_opname'];
$tgl_opname  = $d_h['tgl_opname'];

// =========================================================================
// 4. LOGIKA GUDANG CERDAS (PERBAIKAN NAMA SATUAN KECIL)
// =========================================================================
$is_gudang_utama = ($id_gudang == 1);

if ($is_gudang_utama) {
    // Gudang Utama: Pakai Konversi Satuan Besar
    $satuan_pilih = "COALESCE(sat_b.nama_satuan, sat_default.nama_satuan)";
    $stok_hitung  = "(s.jumlah / COALESCE(k.nilai_konversi, 1))";
    $konversi_val = "COALESCE(k.nilai_konversi, 1)";
} else {
    // Gudang Oprasional/Produksi: Murni Satuan Kecil dari Master Konversi
    $satuan_pilih = "COALESCE(sat_k.nama_satuan, sat_default.nama_satuan)";
    $stok_hitung  = "s.jumlah";
    $konversi_val = "1"; // Konversi 1 agar saat disimpan tidak terkalikan lagi
}

$sql_stok = "(SELECT 'BB' as tipe, s.id_bb as id_item, b.nama_bb as nama_item, 
              $satuan_pilih as nama_satuan, 
              $stok_hitung as stok_sistem,
              $konversi_val as konversi
              FROM stok_bahan s 
              JOIN master_bahan_baku b ON s.id_bb = b.id_bb 
              JOIN master_satuan sat_default ON b.id_satuan = sat_default.id_satuan
              LEFT JOIN master_konversi k ON b.id_bb = k.id_komponen AND k.tipe_bahan = 'BB'
              LEFT JOIN master_satuan sat_b ON k.satuan_besar = sat_b.id_satuan
              LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan
              WHERE s.id_gudang = '$id_gudang')
             UNION
             (SELECT 'BSJ' as tipe, s.id_bsj as id_item, b.nama_bsj as nama_item, 
              $satuan_pilih as nama_satuan, 
              $stok_hitung as stok_sistem,
              $konversi_val as konversi
              FROM stok_bahan s 
              JOIN master_bahan_setengah_jadi b ON s.id_bsj = b.id_bsj 
              JOIN master_satuan sat_default ON b.id_satuan = sat_default.id_satuan
              LEFT JOIN master_konversi k ON b.id_bsj = k.id_komponen AND k.tipe_bahan = 'BSJ'
              LEFT JOIN master_satuan sat_b ON k.satuan_besar = sat_b.id_satuan
              LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan
              WHERE s.id_gudang = '$id_gudang')";
$q_stok = mysqli_query($koneksi, $sql_stok);

$role = htmlspecialchars($_SESSION['nama_role'] ?? '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Input Opname - SI Resto</title>
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
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <div class="main-header">
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm"><img src="<?= $foto ?>" class="avatar-img rounded-circle" /></div>
                                <span class="profile-username"><span class="op-7">Selamat Datang,</span> <span class="fw-bold"><?= $nama ?></span></span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="page-inner">
                <form id="formOpname" action="proses_stok_opname.php" method="POST">
                    <input type="hidden" name="id_header" value="<?= $id_header ?>">
                    <input type="hidden" name="id_gudang" value="<?= $id_gudang ?>">
                    <input type="hidden" name="kode_opname" value="<?= $kode_opn ?>">

                    <div class="page-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fw-bold mb-1">Input Hasil Opname</h3>
                            <p class="text-muted mb-0">Nota: <b><?= $kode_opn ?></b> | Gudang: <b><?= $nama_gudang ?></b> | Tgl: <b><?= date('d/m/Y', strtotime($tgl_opname)) ?></b></p>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm"><i class="fas fa-save me-1"></i> SIMPAN HASIL</button>
                            <button type="button" onclick="confirmBatal(<?= $id_header ?>)" class="btn btn-danger fw-bold ms-1 shadow-sm">BATAL</button>
                        </div>
                    </div>

                    <div class="card card-round shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="table-opname-input" class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr class="text-uppercase small">
                                            <th class="align-middle">Nama Bahan</th>
                                            <th class="text-center align-middle">Stok Sistem</th>
                                            <th class="text-center align-middle" style="width: 100px;">
                                                Sesuai?<br>
                                                <div class="d-flex justify-content-center mt-1">
                                                    <input type="checkbox" id="checkAll" style="transform: scale(1.3); cursor:pointer;">
                                                </div>
                                            </th>
                                            <th class="text-center align-middle" style="width: 150px;">Stok Fisik</th>
                                            <th class="text-center align-middle">Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $i = 0;
                                        while($row = mysqli_fetch_assoc($q_stok)): 
                                            $i++;
                                            $id_input = $row['tipe'] . "-" . $row['id_item'];
                                            // Mematikan .00 agar rapi (cth: 2.00 jadi 2)
                                            $val_sistem = (float)round($row['stok_sistem'], 2);
                                        ?>
                                        <tr>
                                            <td class="align-middle">
                                                <span class="fw-bold text-dark"><?= $row['nama_item'] ?></span><br>
                                                <small class="text-muted"><?= htmlspecialchars($row['nama_satuan']) ?></small>
                                                <input type="hidden" name="items[<?= $i ?>][id_raw]" value="<?= $id_input ?>">
                                                <input type="hidden" name="items[<?= $i ?>][stok_sistem]" value="<?= $val_sistem ?>">
                                                <input type="hidden" name="items[<?= $i ?>][konversi]" value="<?= $row['konversi'] ?>">
                                            </td>
                                            <td class="text-center align-middle">
                                                <span id="sys_text_<?= $i ?>" class="badge badge-secondary fs-6"><?= $val_sistem ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center">
                                                    <input type="checkbox" class="check-sesuai" data-id="<?= $i ?>" data-val="<?= $val_sistem ?>" style="transform: scale(1.3); cursor:pointer;">
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <input type="number" step="any" name="items[<?= $i ?>][stok_fisik]" 
                                                       id="fisik_<?= $i ?>" class="form-control form-control-sm text-center fw-bold input-fisik" 
                                                       data-id="<?= $i ?>" placeholder="0" required>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span id="diff_<?= $i ?>" class="fw-bold">0</span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var table = $('#table-opname-input').DataTable({
            "pageLength": 25,
            "columnDefs": [{ "orderable": false, "targets": [2, 3] }]
        });

        function calculateDiff(id) {
            let fisik = parseFloat($('#fisik_' + id).val()) || 0;
            let sistem = parseFloat($('#sys_text_' + id).text()) || 0;
            let selisih = fisik - sistem;
            let diffElement = $('#diff_' + id);
            
            // Format angka rapi tanpa .00
            let viewSelisih = parseFloat(selisih.toFixed(2));

            if (selisih > 0) { 
                diffElement.removeClass('text-danger text-dark').addClass('text-success').text('+' + viewSelisih); 
            } else if (selisih < 0) { 
                diffElement.removeClass('text-success text-dark').addClass('text-danger').text(viewSelisih); 
            } else { 
                diffElement.removeClass('text-success text-danger').addClass('text-dark').text('0'); 
            }
        }

        $(document).on('change', '.check-sesuai', function() {
            let id = $(this).data('id');
            let valSistem = $(this).data('val');
            let inputFisik = $('#fisik_' + id);

            if ($(this).is(':checked')) { inputFisik.val(valSistem).prop('readonly', true); }
            else { inputFisik.val('').prop('readonly', false); $('#checkAll').prop('checked', false); }
            calculateDiff(id);
        });

        $('#checkAll').on('change', function() {
            let isChecked = $(this).is(':checked');
            $('.check-sesuai').each(function() {
                $(this).prop('checked', isChecked);
                let id = $(this).data('id');
                let valSistem = $(this).data('val');
                let inputFisik = $('#fisik_' + id);
                if (isChecked) { inputFisik.val(valSistem).prop('readonly', true); }
                else { inputFisik.val('').prop('readonly', false); }
                calculateDiff(id);
            });
        });

        $(document).on('input', '.input-fisik', function() {
            calculateDiff($(this).data('id'));
        });
    });

    // Fungsi BATAL Fix
    function confirmBatal(id) {
        Swal.fire({
            title: 'Batalkan Opname?',
            text: "Nota ini akan dihapus karena belum diproses!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Lanjut Input'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "delete_opname.php?id=" + id;
            }
        })
    }
</script>
</body>
</html>