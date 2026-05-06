<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. CEK LOGIN: Jika session username kosong, berarti belum login
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    // Arahkan ke halaman login di root folder
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

// 2. FUNGSI CEK AKSES: Untuk membatasi CRUD (Lapis 3)
// Fungsi ini mengembalikan true jika user boleh melakukan aksi
function can_edit() {
    $role = strtolower($_SESSION['nama_role'] ?? '');
    // Owner tidak boleh CRUD (hanya Read)
    if ($role == 'owner') {
        return false;
    }
    return true;
}
?>