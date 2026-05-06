<?php
include("../config/koneksi_mysql.php");

// 1. Pastikan parameter ada
if (!isset($_POST['id']) || !isset($_POST['tipe'])) {
    echo '<div class="alert alert-danger">Parameter tidak lengkap.</div>';
    exit();
}

$id = (int)$_POST['id'];
$tipe = mysqli_real_escape_string($koneksi, $_POST['tipe']);

// 2. Ambil Nama Satuan Induk Produk (Dinamis untuk label BSJ)
if ($tipe == 'MENU') {
    $q_induk = mysqli_query($koneksi, "SELECT s.nama_satuan FROM master_menu m 
                JOIN master_satuan s ON m.id_satuan = s.id_satuan WHERE m.id_menu = '$id'");
} else {
    $q_induk = mysqli_query($koneksi, "SELECT s.nama_satuan FROM master_bahan_setengah_jadi b 
                JOIN master_satuan s ON b.id_satuan = s.id_satuan WHERE b.id_bsj = '$id'");
}
$data_induk = mysqli_fetch_assoc($q_induk);
$satuan_produk = $data_induk['nama_satuan'] ?? 'Unit';

// 3. QUERY: Ambil data rincian bahan
$sql = "SELECT b.qty, b.target_hasil,
                COALESCE(bb.nama_bb, bsj.nama_bsj) as nama_bahan,
                s.nama_satuan as satuan_pakai
        FROM master_bom b
        LEFT JOIN master_bahan_baku bb ON b.id_bb = bb.id_bb
        LEFT JOIN master_bahan_setengah_jadi bsj ON b.id_bsj = bsj.id_bsj
        LEFT JOIN master_satuan s ON b.id_satuan = s.id_satuan
        WHERE b.id_induk = '$id' AND b.tipe_bom = '$tipe'
        ORDER BY nama_bahan ASC";

$query = mysqli_query($koneksi, $sql);
$data_array = [];
$target_hasil = 1; 

while($row = mysqli_fetch_assoc($query)){
    $data_array[] = $row;
    $target_hasil = $row['target_hasil']; 
}

// --- PERBAIKAN: LOGIKA HIDDEN TARGET HASIL UNTUK MENU ---
if ($tipe == 'BSJ') {
    echo '
    <div class="alert alert-light border-primary mb-3 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary"><i class="fas fa-bullseye me-2"></i> Target Hasil Resep:</span>
            <span class="badge bg-primary px-3 py-2" style="font-size: 14px;">
                '.(float)$target_hasil.' '.htmlspecialchars($satuan_produk).'
            </span>
        </div>
    </div>';
} else {
    // Jika MENU, tidak menampilkan baris Target Hasil (Hidden) agar modal lebih ringkas
    echo '<div class="mb-2"></div>'; 
}

// 4. TABEL RINCIAN BAHAN
echo '<div class="table-responsive"><table class="table table-bordered table-hover align-middle">';
echo '<thead class="bg-light text-center">
        <tr>
            <th width="10%">NO</th>
            <th class="text-start">NAMA BAHAN</th>
            <th width="25%">QTY BAHAN</th>
            <th width="25%">SATUAN</th>
        </tr>
      </thead><tbody>';

$no = 1;
if (count($data_array) > 0) {
    foreach($data_array as $r) {
        $qty_total = floatval($r['qty']);
        $satuan_final = $r['satuan_pakai'] ?? '-';

        echo "<tr class='text-center'>
                <td>".$no++."</td>
                <td class='text-start fw-bold'>".htmlspecialchars($r['nama_bahan'])."</td>
                <td class='text-dark'>".$qty_total."</td>
                <td class='text-muted'>".htmlspecialchars($satuan_final)."</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center text-muted p-4'>Belum ada rincian bahan.</td></tr>";
}
echo '</tbody></table></div>';
?>