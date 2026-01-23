<?php
// add_menu_keluar.php - Memproses & menyimpan transaksi menu keluar
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: menu_keluar.php");
    exit;
}

$tanggal_keluar = $_POST['tanggal_keluar'];
$catatan = $_POST['catatan'];
$id_items = $_POST['id_item'];
$jumlahs = $_POST['jumlah_keluar'];

// Validasi
if (empty($id_items) || empty($jumlahs) || count($id_items) != count($jumlahs)) {
    $_SESSION['flash_msg'] = "Error: Data menu tidak lengkap.";
    header("Location: menu_keluar.php");
    exit;
}

mysqli_begin_transaction($koneksi);
try {
    // 1. Simpan header
    $stmt_header = mysqli_prepare($koneksi, "INSERT INTO menu_keluar (tanggal_keluar, catatan) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt_header, "ss", $tanggal_keluar, $catatan);
    mysqli_stmt_execute($stmt_header);
    $id_menu_keluar = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt_header);

    // Siapkan statement
    $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO menu_keluar_detail (id_menu, id_item, jumlah_keluar) VALUES (?, ?, ?)");
    $stmt_update_stok = mysqli_prepare($koneksi, "UPDATE inventory_stok SET jumlah_stok = jumlah_stok - ? WHERE id_item = ? AND id_gudang = ?");
    $stmt_get_bom = mysqli_prepare($koneksi, "SELECT komponen_id, qty FROM tabel_bom WHERE produk_id = ?");

    // 2. Loop melalui setiap menu yang keluar
    for ($i = 0; $i < count($id_items); $i++) {
        $id_item = (int)$id_items[$i];
        $jumlah = (float)$jumlahs[$i];

        if ($id_item <= 0 || $jumlah <= 0) continue;

        // Simpan ke detail
        mysqli_stmt_bind_param($stmt_detail, "iid", $id_menu_keluar, $id_item, $jumlah);
        mysqli_stmt_execute($stmt_detail);

        // 3. "BOM EXPLOSION" - Pengurangan Stok
        mysqli_stmt_bind_param($stmt_get_bom, "i", $id_item);
        mysqli_stmt_execute($stmt_get_bom);
        $result_bom = mysqli_stmt_get_result($stmt_get_bom);

        while ($komponen = mysqli_fetch_assoc($result_bom)) {
            $id_komponen = $komponen['komponen_id'];
            $qty_dibutuhkan = $komponen['qty'] * $jumlah; // Qty per resep dikali jumlah porsi
            $id_gudang_asal = 3; // Asumsi semua bahan diambil dari Gudang Oprasional (ID=3)

            // Update stok komponen
            mysqli_stmt_bind_param($stmt_update_stok, "dii", $qty_dibutuhkan, $id_komponen, $id_gudang_asal);
            mysqli_stmt_execute($stmt_update_stok);
        }
    }

    // Tutup statement
    mysqli_stmt_close($stmt_detail);
    mysqli_stmt_close($stmt_update_stok);
    mysqli_stmt_close($stmt_get_bom);

    // Selesai
    mysqli_commit($koneksi);
    $_SESSION['flash_msg'] = "Transaksi menu keluar berhasil dicatat!";

} catch (mysqli_sql_exception $exception) {
    mysqli_rollback($koneksi);
    $_SESSION['flash_msg'] = "Error: Gagal menyimpan data. Terjadi kesalahan database.";
}

header("Location: menu_keluar.php");
exit;
?>
