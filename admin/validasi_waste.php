<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. PROTEKSI AKSES: Hanya Admin, Purchasing, dan Owner yang boleh masuk ke file ini
$user_role = strtolower($_SESSION['nama_role'] ?? '');
if (!in_array($user_role, ['admin', 'purchasing', 'owner'])) {
    header("Location: waste.php?status=error&msg=Akses Ditolak! Hanya bagian Purchasing atau Admin yang berhak memvalidasi stok.");
    exit();
}

if (isset($_GET['id'])) {
    $id_header = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // 2. CEK STATUS: Pastikan datanya ada dan statusnya masih 'Pending'
    $q_h = mysqli_query($koneksi, "SELECT id_gudang, kode_waste FROM header_waste WHERE id_header_waste = '$id_header' AND status_validasi = 'Pending'");
    $d_h = mysqli_fetch_assoc($q_h);
    
    if ($d_h) {
        $id_gudang = $d_h['id_gudang'];
        $kode_waste = $d_h['kode_waste'];

        // Mulai Transaksi Database (Biar aman kalau listrik mati / error di tengah jalan, data nggak setengah-setengah)
        mysqli_begin_transaction($koneksi);
        
        try {
            // 3. AMBIL SEMUA ITEM YANG DIINPUT STAF DARI TABEL DETAIL
            $q_det = mysqli_query($koneksi, "SELECT * FROM detail_waste WHERE id_header_waste = '$id_header'");
            
            while ($row = mysqli_fetch_assoc($q_det)) {
                $qty = (float)$row['qty_waste'];
                $alasan = mysqli_real_escape_string($koneksi, $row['alasan']);
                
                // Cek apakah ini Bahan Baku (BB) atau Bahan Setengah Jadi (BSJ)
                $tipe = !empty($row['id_bb']) ? 'BB' : 'BSJ';
                $col = ($tipe == 'BB') ? 'id_bb' : 'id_bsj';
                $val_id = $row[$col];

                // A. EKSEKUSI POTONG STOK
                $sql_update_stok = "UPDATE stok_bahan SET jumlah = jumlah - $qty WHERE $col = '$val_id' AND id_gudang = '$id_gudang'";
                mysqli_query($koneksi, $sql_update_stok);
                
                // B. AMBIL SISA STOK TERBARU UNTUK DICATAT DI LOG
                $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$val_id' AND id_gudang = '$id_gudang'");
                $sisa = mysqli_fetch_assoc($q_sisa)['jumlah'] ?? 0;

                // C. CATAT KE LOG STOK
                $ket_log = "Waste: $kode_waste (Disetujui Purchasing - $alasan)";
                if ($tipe == 'BB') {
                    $sql_log = "INSERT INTO log_stok (id_bb, id_bsj, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan, tgl_log) 
                                VALUES ('$val_id', NULL, 0, '$qty', 'Waste', '$id_gudang', '$sisa', '$ket_log', NOW())";
                } else {
                    $sql_log = "INSERT INTO log_stok (id_bb, id_bsj, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan, tgl_log) 
                                VALUES (NULL, '$val_id', 0, '$qty', 'Waste', '$id_gudang', '$sisa', '$ket_log', NOW())";
                }
                mysqli_query($koneksi, $sql_log);
            }

            // 4. UBAH STATUS HEADER JADI DISETUJUI
            mysqli_query($koneksi, "UPDATE header_waste SET status_validasi = 'Disetujui' WHERE id_header_waste = '$id_header'");

            // Simpan semua perubahan
            mysqli_commit($koneksi);
            header("Location: waste.php?status=success&msg=Mantap! Waste $kode_waste berhasil divalidasi dan stok telah terpotong.");
            exit();

        } catch (Exception $e) {
            // Kalau ada error, batalkan semua pemotongan stok
            mysqli_rollback($koneksi);
            header("Location: waste.php?status=error&msg=Gagal memvalidasi stok. Terjadi kesalahan sistem.");
            exit();
        }
    } else {
        // Kalau data tidak ditemukan atau ternyata sudah divalidasi
        header("Location: waste.php?status=error&msg=Data tidak valid atau sudah disetujui sebelumnya.");
        exit();
    }
} else {
    header("Location: waste.php");
    exit();
}
?>