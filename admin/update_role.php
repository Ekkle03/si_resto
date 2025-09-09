<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// 1. Pastikan request method adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Validasi data wajib
    if (empty($_POST['id_role']) || empty($_POST['nama_role'])) {
        header("Location: master_role.php?msg=Error:%20Data%20tidak%20lengkap.");
        exit();
    }

    // 3. Ambil data dari form
    $id_role   = $_POST['id_role'];
    $nama_role = trim($_POST['nama_role']);

    // 4. Siapkan query UPDATE dengan prepared statement
    $sql  = "UPDATE master_role SET nama_role = ? WHERE id_role = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // 5. Bind parameter ('si' = string, integer)
        mysqli_stmt_bind_param($stmt, "si", $nama_role, $id_role);

        // 6. Eksekusi
        if (mysqli_stmt_execute($stmt)) {
            header("Location: master_role.php?msg=Data%20berhasil%20diupdate");
            exit();
        } else {
            header("Location: master_role.php?msg=Error:%20Gagal%20mengupdate%20data.");
            exit();
        }

        // 7. Tutup statement
        mysqli_stmt_close($stmt);
    } else {
        header("Location: master_role.php?msg=Error:%20Terjadi%20kesalahan%20pada%20sistem.");
        exit();
    }

} else {
    // 8. Akses langsung selain POST → redirect
    header("Location: master_role.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>