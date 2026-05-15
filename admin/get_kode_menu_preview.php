<?php
include("../config/koneksi_mysql.php");

// 1. Tangkap Data dari Ajax
$data_menu = json_decode($_POST['data_menu'], true);
$tgl_trx   = mysqli_real_escape_string($koneksi, $_POST['tgl_trx']);

$html = "";
$semua_item_valid = true;
$kebutuhan_bahan = []; // Array untuk simulasi total kebutuhan resep

// ==============================================================
// VALIDASI 1: CEK DUPLIKASI TANGGAL TRANSAKSI (UTAMA)
// ==============================================================
$cek_tgl = mysqli_query($koneksi, "SELECT id_jual FROM menu_terjual WHERE tanggal_transaksi = '$tgl_trx'");
if (mysqli_num_rows($cek_tgl) > 0) {
    $tgl_indo = date('d/m/Y', strtotime($tgl_trx));
    echo json_encode([
        'status'  => 'error', 
        'message' => "Laporan penjualan untuk tanggal <b>$tgl_indo</b> sudah pernah di-upload.<br><br>Satu tanggal hanya boleh di-upload satu kali. Hapus riwayat tanggal tersebut jika ingin mengulang."
    ]);
    exit();
}

// ==============================================================
// VALIDASI 2: PENGENALAN ITEM & SIMULASI RESEP
// ==============================================================
foreach ($data_menu as $item) {
    $nama = mysqli_real_escape_string($koneksi, $item['nama']);
    $qty_laku = (float)$item['qty'];

    // Skenario A: Cek di Master Menu
    $q_menu = mysqli_query($koneksi, "SELECT id_menu, kode_menu FROM master_menu WHERE nama_menu = '$nama'");
    
    if (mysqli_num_rows($q_menu) > 0) {
        $row = mysqli_fetch_assoc($q_menu);
        $id_menu = $row['id_menu'];
        $kode_internal = "<span class='badge badge-primary'>{$row['kode_menu']}</span>";
        
        // Bongkar resep (BOM) untuk simulasi stok
        $q_bom = mysqli_query($koneksi, "SELECT * FROM master_bom WHERE id_induk = '$id_menu' AND tipe_bom = 'MENU'");
        while ($bom = mysqli_fetch_assoc($q_bom)) {
            $jumlah_pakai = (float)$bom['qty'] * $qty_laku;
            $unit_di_bom = (int)$bom['id_satuan'];

            if (!empty($bom['id_bb'])) {
                $id_bb = $bom['id_bb'];
                $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_kecil FROM master_konversi WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1");
                $d_konv = mysqli_fetch_assoc($q_konv);
                $qty_potong = ($d_konv && $unit_di_bom != (int)$d_konv['satuan_kecil']) ? $jumlah_pakai * (float)$d_konv['nilai_konversi'] : $jumlah_pakai;
                $kebutuhan_bahan['BB'][$id_bb] = ($kebutuhan_bahan['BB'][$id_bb] ?? 0) + $qty_potong;
            } 
            elseif (!empty($bom['id_bsj'])) {
                $id_bsj = $bom['id_bsj'];
                $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_kecil FROM master_konversi WHERE id_komponen = '$id_bsj' AND tipe_bahan = 'BSJ' LIMIT 1");
                $d_konv = mysqli_fetch_assoc($q_konv);
                $qty_potong = ($d_konv && $unit_di_bom != (int)$d_konv['satuan_kecil']) ? $jumlah_pakai * (float)$d_konv['nilai_konversi'] : $jumlah_pakai;
                $kebutuhan_bahan['BSJ'][$id_bsj] = ($kebutuhan_bahan['BSJ'][$id_bsj] ?? 0) + $qty_potong;
            }
        }
    } else {
        // Skenario B: Cek di Master Bahan Baku (Contoh: Packaging/Kresek)
        $q_bb = mysqli_query($koneksi, "SELECT id_bb, kode_bb FROM master_bahan_baku WHERE nama_bb = '$nama'");
        if (mysqli_num_rows($q_bb) > 0) {
            $row_bb = mysqli_fetch_assoc($q_bb);
            $id_bb = $row_bb['id_bb'];
            $kode_internal = "<span class='badge badge-info'>{$row_bb['kode_bb']}</span>";
            $kebutuhan_bahan['BB'][$id_bb] = ($kebutuhan_bahan['BB'][$id_bb] ?? 0) + $qty_laku;
        } else {
            $kode_internal = "<span class='badge badge-danger'>Tidak Ditemukan</span>";
            $semua_item_valid = false;
        }
    }

    $html .= "<tr>
                <td class='text-center'>$kode_internal</td>
                <td class='text-start ps-3 fw-bold'>{$item['nama']}</td>
                <td class='text-center fw-bold text-success'>$qty_laku</td>
              </tr>";
}

if (!$semua_item_valid) {
    echo json_encode(['status' => 'error', 'message' => "Ada nama menu di CSV yang <b>TIDAK DIKENAL</b>.<br><br>Samakan ejaan CSV dengan data Master di sistem."]);
    exit();
}

// ==============================================================
// VALIDASI 3: CEK STOK GUDANG OPRASIONAL (TITIPAN DOSEN)
// ==============================================================
$error_stok = [];
$id_gudang_ops = 2; 

// Cek Bahan Baku
if (isset($kebutuhan_bahan['BB'])) {
    foreach ($kebutuhan_bahan['BB'] as $id_bb => $butuh) {
        $q_stok = mysqli_query($koneksi, "SELECT s.jumlah, b.nama_bb FROM master_bahan_baku b 
                                          LEFT JOIN stok_bahan s ON b.id_bb = s.id_bb AND s.id_gudang = '$id_gudang_ops'
                                          WHERE b.id_bb = '$id_bb'");
        $d_stok = mysqli_fetch_assoc($q_stok);
        $stok_ada = (float)($d_stok['jumlah'] ?? 0);
        if ($stok_ada < $butuh) {
            $error_stok[] = "<li><b>{$d_stok['nama_bb']}</b>: Kurang " . round($butuh - $stok_ada, 2) . "</li>";
        }
    }
}

// Cek BSJ
if (isset($kebutuhan_bahan['BSJ'])) {
    foreach ($kebutuhan_bahan['BSJ'] as $id_bsj => $butuh) {
        $q_stok = mysqli_query($koneksi, "SELECT s.jumlah, b.nama_bsj FROM master_bahan_setengah_jadi b 
                                          LEFT JOIN stok_bahan s ON b.id_bsj = s.id_bsj AND s.id_gudang = '$id_gudang_ops'
                                          WHERE b.id_bsj = '$id_bsj'");
        $d_stok = mysqli_fetch_assoc($q_stok);
        $stok_ada = (float)($d_stok['jumlah'] ?? 0);
        if ($stok_ada < $butuh) {
            $error_stok[] = "<li><b>{$d_stok['nama_bsj']}</b>: Kurang " . round($butuh - $stok_ada, 2) . "</li>";
        }
    }
}

if (count($error_stok) > 0) {
    $err_msg = "<div class='text-start'><b>STOK GUDANG OPERASIONAL TIDAK CUKUP!</b><br>Kekurangan bahan berikut:<br><ul class='text-danger mt-2'>" . implode("", $error_stok) . "</ul>Harap isi stok gudang operasional dulu.</div>";
    echo json_encode(['status' => 'error', 'message' => $err_msg]);
    exit();
}

// JIKA SEMUA LOLOS
echo json_encode(['status' => 'success', 'html' => $html]);
?>