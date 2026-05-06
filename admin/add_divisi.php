<?php
// Selalu mulai session di awal
session_start();
include("../config/auth.php");
// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Fungsi untuk generate kode divisi otomatis: DIV-001, DIV-002, ...
function generateKodeDivisi($koneksi) {
    // Ambil kode_divisi terakhir berdasarkan id_divisi terbesar
    $query  = "SELECT kode_divisi FROM master_divisi ORDER BY id_divisi DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row      = mysqli_fetch_assoc($result);
        $lastKode = $row['kode_divisi']; // contoh: DIV-007

        // Ambil bagian angka setelah "DIV-"
        $lastNum  = (int) substr($lastKode, 4); // "007" -> 7
        $nextNum  = $lastNum + 1;
    } else {
        // Kalau belum ada data sama sekali
        $nextNum = 1;
    }

    // Bentuk kode baru dengan padding 3 digit
    return "DIV-" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);
}

// 1. Cek apakah form sudah disubmit dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Ambil data dari form dan bersihkan dari spasi ekstra
    $nama_divisi = trim($_POST['nama_divisi'] ?? '');

    // 3. Validasi sederhana: pastikan nama divisi tidak kosong
    if ($nama_divisi === '') {
        $pesan_error = "Nama divisi tidak boleh kosong.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 4. Generate kode divisi otomatis
    $kode_divisi = generateKodeDivisi($koneksi);

    // 5. Siapkan query INSERT menggunakan prepared statement (lebih aman)
    $sql  = "INSERT INTO master_divisi (kode_divisi, nama_divisi) VALUES (?, ?)";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        $pesan_error = "Gagal menyiapkan query: " . mysqli_error($koneksi);
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 6. Bind parameter ke statement
    // 's' berarti tipe datanya adalah string
    mysqli_stmt_bind_param($stmt, "ss", $kode_divisi, $nama_divisi);

    // 7. Eksekusi statement dan cek hasilnya
    if (mysqli_stmt_execute($stmt)) {
        $pesan_sukses = "Data divisi berhasil ditambahkan dengan kode $kode_divisi.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_sukses));
        exit();
    } else {
        $pesan_error = "Error: " . mysqli_stmt_error($stmt);
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 8. Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika halaman diakses tanpa metode POST, redirect ke halaman utama
    header("Location: master_divisi.php");
    exit();
}

// Tutup koneksi database
mysqli_close($koneksi);
?>
