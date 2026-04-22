<?php
include("../config/koneksi_mysql.php");

if (isset($_POST['id'])) {
    $id_pb = (int)$_POST['id'];

    $sql = "SELECT 
                dp.*, 
                bb.nama_bb, 
                bb.kode_bb,
                s_kecil.nama_satuan AS satuan_asli,
                mk.nilai_konversi,
                s_besar.nama_satuan AS satuan_konversi
            FROM detail_pembelian dp
            JOIN master_bahan_baku bb ON dp.id_bb = bb.id_bb
            JOIN master_satuan s_kecil ON bb.id_satuan = s_kecil.id_satuan
            LEFT JOIN master_konversi mk ON bb.id_bb = mk.id_komponen AND mk.tipe_bahan = 'BB'
            LEFT JOIN master_satuan s_besar ON mk.satuan_besar = s_besar.id_satuan
            WHERE dp.id_pembelian = '$id_pb'";
    
    $query = mysqli_query($koneksi, $sql);

    if (mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            
            if (!empty($row['nilai_konversi']) && $row['nilai_konversi'] > 0) {
                $qty_tampil = $row['qty_beli'] / $row['nilai_konversi'];
                $satuan_tampil = $row['satuan_konversi'];
            } else {
                $qty_tampil = $row['qty_beli'];
                $satuan_tampil = $row['satuan_asli'];
            }

            $qty_clean = (float)$qty_tampil;

            echo "<tr class='item-row'>
                    <td class='text-center align-middle fw-bold'>{$row['kode_bb']}</td>
                    <td class='align-middle nama-bb-td'>
                        <input type='hidden' name='id_bb[]' value='{$row['id_bb']}'>
                        {$row['nama_bb']}
                    </td>
                    <td class='text-center bg-light fw-bold align-middle'>
                        {$qty_clean}
                        <input type='hidden' class='max-qty' value='{$qty_clean}'>
                    </td>
                    <td>
                        <input type='number' name='qty_terima[]' class='form-control form-control-sm text-center input-qty text-success' step='any' value='{$qty_clean}' min='0' required style='font-weight:700;'>
                    </td>
                    <td>
                        <input type='number' name='qty_rusak[]' class='form-control form-control-sm text-center input-qty-rusak text-danger' step='any' value='0' min='0' placeholder='0' required style='font-weight:700;'>
                    </td>
                    <td class='text-center text-muted align-middle'>{$satuan_tampil}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center text-danger py-4'>Data bahan tidak ditemukan dalam PB ini.</td></tr>";
    }
}
?>