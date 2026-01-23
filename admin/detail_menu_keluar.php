<?php
session_start();
include("../config/koneksi_mysql.php");

$id_menu = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_menu <= 0) {
    echo "<p class='text-danger'>ID tidak valid.</p>";
    exit;
}

// Ambil data header
$header = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM menu_keluar WHERE id_menu = $id_menu"));
if (!$header) {
    echo "<p class='text-danger'>Data tidak ditemukan.</p>";
    exit;
}

// Ambil data detail
$details = [];
$q_detail = mysqli_query($koneksi, "
    SELECT mkd.jumlah_keluar, i.nama_item, s.nama_satuan
    FROM menu_keluar_detail mkd
    JOIN master_item i ON mkd.id_item = i.id_item
    LEFT JOIN master_satuan s ON i.id_satuan = s.id_satuan
    WHERE mkd.id_menu = $id_menu
");
if ($q_detail) while($r = mysqli_fetch_assoc($q_detail)) $details[] = $r;
?>
<dl class="row">
    <dt class="col-sm-3">ID Transaksi</dt>
    <dd class="col-sm-9">: <?= htmlspecialchars($header['id_menu']) ?></dd>
    <dt class="col-sm-3">Tanggal</dt>
    <dd class="col-sm-9">: <?= date('d F Y H:i', strtotime($header['tanggal_keluar'])) ?></dd>
    <dt class="col-sm-3">Catatan</dt>
    <dd class="col-sm-9">: <?= htmlspecialchars($header['catatan'] ?: '-') ?></dd>
</dl>
<hr>
<h5 class="mb-3">Rincian Menu</h5>
<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th>No</th>
            <th>Nama Menu</th>
            <th class="text-end">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($details)): ?>
        <tr><td colspan="3" class="text-center">Tidak ada rincian.</td></tr>
        <?php else: $no=1; foreach($details as $d): ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($d['nama_item']) ?></td>
            <td class="text-end"><?= rtrim(rtrim(number_format($d['jumlah_keluar'], 2, ',', '.'),'0'),',') ?> <?= htmlspecialchars($d['nama_satuan']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
