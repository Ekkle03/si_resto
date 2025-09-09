<?php
// 1) Mulai Session
session_start();

// 2) Koneksi DB
include("config/koneksi_mysql.php");
if (!$koneksi) {
    error_log("Koneksi database gagal.");
    $_SESSION['error_message'] = 'Tidak dapat terhubung ke server.';
    header("Location: index.php");
    exit;
}

// 3) Validasi input
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error_message'] = 'Username dan password harus diisi.';
    header("Location: index.php");
    exit;
}

$username = trim($_POST['username']);
$password = (string)$_POST['password'];

// 4) Ambil user dari master_karyawan (+ role)
$sql = "SELECT
            k.id_karyawan,
            k.username,
            k.password,
            k.nama_lengkap,
            k.id_role,
            r.nama_role
        FROM master_karyawan k
        LEFT JOIN master_role r ON k.id_role = r.id_role
        WHERE k.username = ?
        LIMIT 1";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    error_log("Prepare statement gagal: " . $koneksi->error);
    $_SESSION['error_message'] = 'Terjadi kesalahan pada sistem.';
    header("Location: index.php");
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

// 5) Cek user & verifikasi password
if ($res && $res->num_rows === 1) {
    $data = $res->fetch_assoc();

    if (!empty($data['password']) && password_verify($password, $data['password'])) {
        // Lindungi dari session fixation
        session_regenerate_id(true);

        // 6) Set session lengkap
        $_SESSION['id_karyawan']  = (int)$data['id_karyawan'];
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['id_role']      = $data['id_role'];             // bisa null
        $_SESSION['nama_role']    = $data['nama_role'] ?? null;   // bisa null
        $_SESSION['login_status'] = true;

        // 7) Redirect sesuai role (fallback ke dashboard umum)
        $role = strtolower(trim((string)($data['nama_role'] ?? '')));
        switch ($role) {
            case 'owner':
                header("Location: admin/dashboard_owner.php"); break;
            case 'admin':
                header("Location: admin/dashboard_admin.php"); break;
            case 'kasir':
                header("Location: admin/dashboard_kasir.php"); break;
            case 'staf gudang':
                header("Location: admin/dashboard_gudang.php"); break;
            default:
                header("Location: admin/dashboard.php"); // fallback
        }
        exit;

    } else {
        $_SESSION['error_message'] = "Username atau password salah.";
        header("Location: index.php");
        exit;
    }
} else {
    $_SESSION['error_message'] = 'Username tidak terdaftar.';
    header("Location: index.php");
    exit;
}

$stmt->close();
$koneksi->close();
