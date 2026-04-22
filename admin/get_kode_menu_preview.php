<?php
include("../config/koneksi_mysql.php");

$data_menu = json_decode($_POST['data_menu'], true);
$html = "";

foreach ($data_menu as $item) {
    $nama = mysqli_real_escape_string($koneksi, $item['nama']);
    $qty = $item['qty'];

    // Cari kode_menu di database
    $q = mysqli_query($koneksi, "SELECT kode_menu FROM master_menu WHERE nama_menu = '$nama'");
    $row = mysqli_fetch_assoc($q);
    
    // Kalau gak ketemu, kasih tanda merah biar kamu tahu ada menu yang gak sinkron
    $kode_internal = $row ? $row['kode_menu'] : "<span class='text-danger'>TIDAK DITEMUKAN</span>";

    $html .= "<tr>
                <td>$kode_internal</td>
                <td>{$item['nama']}</td>
                <td class='text-center fw-bold'>$qty</td>
              </tr>";
}

echo $html;
?>