<?php
// simpan_penerimaan.php - Memproses dan menyimpan data penerimaan dari session ke database
session_start();
include("../config/koneksi_mysql.php");

// Pastikan ada data di keranjang dan diakses via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['keranjang_penerimaan']['detail'])) {
    $_SESSION['flash_msg'] = "Error: Tidak ada item untuk disimpan atau akses tidak sah.";
    header("Location: penerimaan.php");
    exit;
}

// Ambil data dari session
$header = $_SESSION['keranjang_penerimaan']['header'];
$detail = $_SESSION['keranjang_penerimaan']['detail'];
$tanggal = $header['tanggal'] ? date('Y-m-d H:i:s', strtotime($header['tanggal'])) : date('Y-m-d H:i:s');
$keterangan = $header['keterangan'] ?? '';

// Mulai transaksi database untuk memastikan semua proses berhasil
mysqli_begin_transaction($koneksi);

try {
    // Langkah 1: Simpan data header ke tabel penerimaan_barang
    $stmt_header = mysqli_prepare($koneksi, "INSERT INTO penerimaan_barang (tanggal_penerimaan, keterangan) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt_header, "ss", $tanggal, $keterangan);
    mysqli_stmt_execute($stmt_header);
    
    // Ambil ID dari header yang baru saja disimpan
    $id_penerimaan = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt_header);

    // Siapkan statement untuk detail dan update stok
    $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO penerimaan_detail (id_penerimaan, id_item, jumlah_penerimaan) VALUES (?, ?, ?)");
    $stmt_update_stok = mysqli_prepare($koneksi, "
        INSERT INTO inventory_stok (id_item, id_gudang, jumlah_stok) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE jumlah_stok = jumlah_stok + VALUES(jumlah_stok)
    ");

    // Langkah 2: Loop melalui setiap item di detail
    foreach ($detail as $item) {
        $id_item = (int)$item['id_item'];
        $jumlah = (float)$item['jumlah'];

        // Simpan ke penerimaan_detail
        mysqli_stmt_bind_param($stmt_detail, "iid", $id_penerimaan, $id_item, $jumlah);
        mysqli_stmt_execute($stmt_detail);

        // Langkah 3 (Logika Cerdas): Tentukan gudang tujuan berdasarkan tipe_bahan
        $q_item_info = mysqli_query($koneksi, "SELECT tipe_bahan FROM master_item WHERE id_item = $id_item");
        $item_info = mysqli_fetch_assoc($q_item_info);
        
        // Asumsi Gudang Utama ID = 1, Gudang Oprasional ID = 3 (sesuaikan jika berbeda di db)
        $id_gudang_tujuan = 1; // Default ke Gudang Utama
        if ($item_info && $item_info['tipe_bahan'] === 'basah') {
            $id_gudang_tujuan = 3; 
        }

        // Langkah 4: Update stok di inventory_stok
        mysqli_stmt_bind_param($stmt_update_stok, "iid", $id_item, $id_gudang_tujuan, $jumlah);
        mysqli_stmt_execute($stmt_update_stok);
    }

    // Tutup statement yang sudah disiapkan
    mysqli_stmt_close($stmt_detail);
    mysqli_stmt_close($stmt_update_stok);

    // Jika semua berhasil, konfirmasi semua perubahan
    mysqli_commit($koneksi);

    // Kosongkan keranjang dan beri pesan sukses
    unset($_SESSION['keranjang_penerimaan']);
    $_SESSION['flash_msg'] = "Transaksi penerimaan barang berhasil disimpan!";
    header("Location: penerimaan.php");
    exit;

} catch (mysqli_sql_exception $exception) {
    // Jika ada satu saja error, batalkan semua perubahan
    mysqli_rollback($koneksi);

    // Tambahkan logging error untuk debugging
    // error_log("Database Error: " . $exception->getMessage());

    $_SESSION['flash_msg'] = "Error: Gagal menyimpan data. Terjadi kesalahan database.";
    header("Location: add_penerimaan.php");
    exit;
}
?>
