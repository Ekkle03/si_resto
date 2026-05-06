<?php
session_start();

// Cek apakah user sudah login, kalau belum lempar ke halaman login
if (empty($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Ambil role dari session
$role = strtolower($_SESSION['nama_role']);

// Arahkan ke file dashboard yang sesuai dengan role
if ($role == 'admin') {
    include 'dashboard_admin.php';
} 
elseif ($role == 'owner') {
    include 'dashboard_owner.php';
} 
elseif ($role == 'purchasing') {
    include 'dashboard_purchasing.php';
} 
elseif ($role == 'staf') {
    include 'dashboard_staf.php';
} 
else {
    // Kalau rolenya aneh/tidak terdaftar
    echo "<h1>Error: Role tidak dikenali!</h1>";
    echo "<a href='../logout.php'>Kembali ke Login</a>";
    exit();
}
?>