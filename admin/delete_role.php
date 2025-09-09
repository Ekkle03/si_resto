<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// 1. Pastikan parameter 'id' ada di URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_role = $_GET['id'];

    // 2. Query DELETE dengan prepared statement
    $sql  = "DELETE FROM master_role WHERE id_role = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // 3. Bind parameter (i = integer)
        mysqli_stmt_bind_param($stmt, "i", $id_role);

        // 4. Eksekusi
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                header("Location: master_role.php?msg=Data%20berhasil%20dihapus");
                exit();
            } else {
                header("Location: master_role.php?msg=Error:%20Data%20tidak%20ditemukan.");
                exit();
            }
        } else {
            header("Location: master_role.php?msg=Error:%20Gagal%20menghapus%20data,%20kemungkinan%20role%20masih%20digunakan.");
            exit();
        }

        mysqli_stmt_close($stmt);
    } else {
        header("Location: master_role.php?msg=Error:%20Terjadi%20kesalahan%20pada%20sistem.");
        exit();
    }

} else {
    header("Location: master_role.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>
