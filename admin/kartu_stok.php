<?php
// kartu_stok.php — Kartu Stok (2 card: Filter + Hasil) | tombol Tampil tidak keluar card
session_start();
include("../config/koneksi_mysql.php");

/* ---------- Dropdown data ---------- */
$items = $gudangs = [];
$q = mysqli_query($koneksi, "SELECT i.id_item, i.nama_item, s.nama_satuan
                             FROM master_item i
                             LEFT JOIN master_satuan s ON s.id_satuan=i.id_satuan
                             ORDER BY i.nama_item ASC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $items[] = $r;

$q = mysqli_query($koneksi, "SELECT id_gudang, nama_gudang FROM master_gudang ORDER BY nama_gudang ASC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $gudangs[] = $r;

/* ---------- Filter ---------- */
$id_item   = isset($_GET['id_item'])   ? (int)$_GET['id_item']   : 0;
$id_gudang = isset($_GET['id_gudang']) ? (int)$_GET['id_gudang'] : 0;
$start     = $_GET['start'] ?? '';
$end       = $_GET['end']   ?? '';

$today = date('Y-m-d');
$default_start = date('Y-m-d', strtotime('-29 days'));
if (!$start || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = $default_start;
if (!$end   || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = $today;

$start_dt = $start.' 00:00:00';
$end_dt   = $end.' 23:59:59';

/* ---------- Info header ---------- */
$nama_item = $nama_satuan = $nama_gudang = '';
if ($id_item > 0) {
  $stmt = mysqli_prepare($koneksi, "SELECT i.nama_item, s.nama_satuan
                                    FROM master_item i
                                    LEFT JOIN master_satuan s ON s.id_satuan=i.id_satuan
                                    WHERE i.id_item=?");
  mysqli_stmt_bind_param($stmt, "i", $id_item);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $nama_item, $nama_satuan);
  mysqli_stmt_fetch($stmt); mysqli_stmt_close($stmt);
}
if ($id_gudang > 0) {
  $stmt = mysqli_prepare($koneksi, "SELECT nama_gudang FROM master_gudang WHERE id_gudang=?");
  mysqli_stmt_bind_param($stmt, "i", $id_gudang);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $nama_gudang);
  mysqli_stmt_fetch($stmt); mysqli_stmt_close($stmt);
}

/* ---------- Saldo awal ---------- */
$saldo_awal = 0.0;
if ($id_item>0 && $id_gudang>0) {
  $stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(CASE WHEN arah='IN' THEN qty ELSE -qty END),0)
                                    FROM inventory_mutasi
                                    WHERE id_item=? AND id_gudang=? AND tanggal < ?");
  mysqli_stmt_bind_param($stmt, "iis", $id_item, $id_gudang, $start_dt);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $saldo_awal);
  mysqli_stmt_fetch($stmt); mysqli_stmt_close($stmt);
}

/* ---------- Mutasi periode ---------- */
$rows = []; $sum_in = 0.0; $sum_out = 0.0;
if ($id_item>0 && $id_gudang>0) {
  $stmt = mysqli_prepare($koneksi, "SELECT id_mutasi, tanggal, sumber, arah, qty, COALESCE(catatan,'') AS catatan
                                    FROM inventory_mutasi
                                    WHERE id_item=? AND id_gudang=? AND tanggal BETWEEN ? AND ?
                                    ORDER BY tanggal ASC, id_mutasi ASC");
  mysqli_stmt_bind_param($stmt, "iiss", $id_item, $id_gudang, $start_dt, $end_dt);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res) while ($r = mysqli_fetch_assoc($res)) {
    if ($r['arah']==='IN')  $sum_in  += (float)$r['qty'];
    if ($r['arah']==='OUT') $sum_out += (float)$r['qty'];
    $rows[] = $r;
  }
  mysqli_stmt_close($stmt);
}

/* ---------- Helper tampilan ---------- */
function dmyhi($ts) { return date('d/m/Y H:i', strtotime($ts)); }
function n3($v)    { return number_format((float)$v, 3, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Kartu Stok - Sistem Resto</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />
  <script src="assets/js/plugin/webfont/webfont.min.js"></script>
  <script>WebFont.load({google:{families:["Public Sans:300,400,500,600,700"]},custom:{families:["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"],urls:["assets/css/fonts.min.css"]}});</script>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/css/plugins.min.css" />
  <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
  <style>
    .card { border-radius: 14px; }
    /* FILTER CARD */
    .filter-card .card-body { overflow: hidden; } /* cegah isi keluar radius card */
    .filter-card .form-select, .filter-card .form-control { min-width: 0; } /* biar input ikut lebar kolom */
    .btn-big { height: 42px; display: inline-flex; align-items: center; gap:.5rem; white-space: nowrap; width:100%; }
    @media (min-width: 768px){ .btn-big { width:auto; } } /* desktop: auto width */

    /* TABLE */
    .table-kartu { font-size: .95rem; }
    .table-kartu thead th { text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
    .table-kartu td, .table-kartu th { vertical-align: middle; }
    .num { text-align: right; font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
    .badge-in  { background:#e8fff1; color:#12824c; border:1px solid #b9f0cf; font-weight:600; }
    .badge-out { background:#fff0f0; color:#b22e2e; border:1px solid #f5c2c2; font-weight:600; }
    .tag { background:#f1f5f9; border:1px solid #e2e8f0; padding:.25rem .5rem; border-radius:999px; font-weight:600; font-size:.8rem; }
    .table-secondary { --bs-table-bg: #f6f7fb !important; }
    .summary-row th { background:#f8f9fa; }
    .subtle { color:#6b7280; }
    @media print {
      .no-print { display:none !important; }
      .card, .page-inner, .container { box-shadow:none !important; }
      body { background:white; }
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
                <div class="avatar-sm"><img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" /></div>
                <span class="profile-username"><span class="op-7">Selamat Datang,</span> <span class="fw-bold"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></span></span>
              </a>
              <ul class="dropdown-menu dropdown-user animated fadeIn">
                <div class="dropdown-user-scroll scrollbar-outer">
                  <li>
                    <div class="user-box">
                      <div class="avatar-lg"><img src="assets/img/profile.jpg" class="avatar-img rounded" /></div>
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
        <div class="page-header">
          <h3 class="fw-bold mb-3">Kartu Stok</h3>
        </div>

        <!-- CARD #1: FILTER -->
        <div class="card shadow-sm filter-card mb-3">
          <div class="card-body">
            <!-- mx-0: hilangkan margin negatif .row supaya gak nembus padding card -->
            <form class="row gy-3 gx-3 align-items-end mx-0" method="get" action="">
              <div class="col-12 col-xl-4">
                <label class="form-label">Item</label>
                <select name="id_item" class="form-select" required>
                  <option value="">Pilih Item...</option>
                  <?php foreach ($items as $it): ?>
                    <option value="<?= (int)$it['id_item'] ?>" <?= $id_item==$it['id_item']?'selected':'' ?>>
                      <?= htmlspecialchars($it['nama_item']) ?><?= $it['nama_satuan']?' ('.htmlspecialchars($it['nama_satuan']).')':'' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-xl-3">
                <label class="form-label">Gudang</label>
                <select name="id_gudang" class="form-select" required>
                  <option value="">Pilih Gudang...</option>
                  <?php foreach ($gudangs as $gd): ?>
                    <option value="<?= (int)$gd['id_gudang'] ?>" <?= $id_gudang==$gd['id_gudang']?'selected':'' ?>>
                      <?= htmlspecialchars($gd['nama_gudang']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label">Dari</label>
                <input type="date" class="form-control" name="start" value="<?= htmlspecialchars($start) ?>">
              </div>

              <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label">Sampai</label>
                <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($end) ?>">
              </div>

              <!-- Tombol: full width di mobile, auto di desktop; tetap di dalam card -->
              <div class="col-12 col-md-4 col-xl-auto d-grid ms-xl-auto">
                <button class="btn btn-primary btn-big">
                  <i class="fa fa-search"></i><span>Tampil</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- CARD #2: HASIL -->
        <div class="card shadow-sm">
          <div class="card-body">
            <?php if ($id_item>0 && $id_gudang>0): ?>
              <!-- HEADER ITEM/GUDANG -->
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5 class="mb-1"><?= htmlspecialchars($nama_item ?: '#'.$id_item) ?>
                    <small class="text-muted"><?= $nama_satuan ? ' ('.htmlspecialchars($nama_satuan).')' : '' ?></small>
                  </h5>
                  <div class="subtle">Gudang: <strong><?= htmlspecialchars($nama_gudang ?: '#'.$id_gudang) ?></strong></div>
                  <div class="subtle small">Periode: <?= htmlspecialchars($start) ?> &rarr; <?= htmlspecialchars($end) ?></div>
                </div>
                <div class="no-print">
                  <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-print"></i> Cetak
                  </button>
                </div>
              </div>

              <!-- TABEL KARTU -->
              <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered table-kartu">
                  <thead class="table-light">
                    <tr>
                      <th style="width:170px">Tanggal</th>
                      <th style="width:180px">Sumber</th>
                      <th style="width:100px" class="text-center">Arah</th>
                      <th style="width:130px" class="num">Masuk</th>
                      <th style="width:130px" class="num">Keluar</th>
                      <th>Catatan</th>
                      <th style="width:150px" class="num">Saldo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-secondary">
                      <td colspan="6"><em>Saldo awal s.d. <?= htmlspecialchars($start_dt) ?></em></td>
                      <td class="num"><strong><?= n3($saldo_awal) ?></strong></td>
                    </tr>
                    <?php
                      $saldo = (float)$saldo_awal;
                      if (count($rows) > 0):
                        foreach ($rows as $r):
                          $in  = $r['arah']==='IN'  ? (float)$r['qty'] : 0.0;
                          $out = $r['arah']==='OUT' ? (float)$r['qty'] : 0.0;
                          $saldo += ($in - $out);
                          $tag  = "<span class='tag'>".htmlspecialchars($r['sumber'])."</span>";
                          $arah = $r['arah']==='IN'
                                  ? "<span class='badge badge-in px-2'>IN</span>"
                                  : "<span class='badge badge-out px-2'>OUT</span>";
                    ?>
                      <tr>
                        <td><?= dmyhi($r['tanggal']) ?></td>
                        <td><?= $tag ?></td>
                        <td class="text-center"><?= $arah ?></td>
                        <td class="num"><?= $in  ? n3($in)  : '-' ?></td>
                        <td class="num"><?= $out ? n3($out) : '-' ?></td>
                        <td><?= htmlspecialchars($r['catatan']) ?></td>
                        <td class="num fw-semibold"><?= n3($saldo) ?></td>
                      </tr>
                    <?php
                        endforeach;
                      else:
                    ?>
                      <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                          <i class="fa fa-inbox me-2"></i> Tidak ada mutasi pada periode ini.
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                  <tfoot>
                    <tr class="summary-row">
                      <th colspan="3" class="text-end">Total Periode</th>
                      <th class="num"><?= n3($sum_in) ?></th>
                      <th class="num"><?= n3($sum_out) ?></th>
                      <th class="text-end">Perubahan</th>
                      <th class="num"><?= n3($saldo_awal + $sum_in - $sum_out) ?></th>
                    </tr>
                    <tr class="summary-row">
                      <th colspan="6" class="text-end">Saldo Akhir s.d. <?= htmlspecialchars($end_dt) ?></th>
                      <th class="num"><?= n3($saldo) ?></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php else: ?>
              <div class="alert alert-info mb-0">
                Silakan pilih <strong>Item</strong> dan <strong>Gudang</strong>, lalu atur periode pada card di atas untuk menampilkan kartu stok.
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div> <!-- container -->
  </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
