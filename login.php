<?php
// 1. Memulai Session
session_start();

// 2. Hubungkan ke database
include("config/koneksi_mysql.php");

// Cek jika koneksi gagal
if (!$koneksi) {
    error_log("Koneksi database gagal.");
    $_SESSION['error_message'] = 'Tidak dapat terhubung ke server.';
    header("Location: index.php");
    exit;
}

// 3. Cek jika input username atau password kosong
// PERUBAHAN 1: Cek 'username' bukan 'email'
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error_message'] = 'Username dan password harus diisi.';
    header("Location: index.php");
    exit;
}

// 4. Ambil data dari form
// PERUBAHAN 2: Ambil 'username' dari form
$username = trim($_POST['username']);
$password = $_POST['password'];

// 5. PERUBAHAN 3: Query baru yang menggabungkan 3 tabel (master_user, master_karyawan, master_role)
$sql = "SELECT 
            mu.id_user, mu.username, mu.password, mu.status, mu.id_karyawan, mu.id_role,
            mk.nama_lengkap,
            mr.nama_role
        FROM 
            master_user mu
        JOIN 
            master_karyawan mk ON mu.id_karyawan = mk.id_karyawan
        JOIN 
            master_role mr ON mu.id_role = mr.id_role
        WHERE 
            mu.username = ?";

// 6. Gunakan prepared statement (kode ini sudah bagus, kita pertahankan)
$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    error_log("Prepare statement gagal: " . $koneksi->error);
    $_SESSION['error_message'] = 'Terjadi kesalahan pada sistem.';
    header("Location: index.php");
    exit;
}

// PERUBAHAN 4: Bind parameter '$username'
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// 7. Cek apakah user ditemukan
if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    // 8. Cek apakah akun aktif
    if ($data['status'] === 'aktif') {
        
        // 9. Verifikasi password (tanpa trim, karena hash tidak perlu di-trim)
        if (password_verify($password, $data['password'])) {
            
            session_regenerate_id(true);
            
            // 10. PERUBAHAN 5: Simpan data user yang baru ke session
            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['id_karyawan'] = $data['id_karyawan']; // Simpan ini, penting!
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap']; // Diambil dari tabel karyawan
            $_SESSION['id_role'] = $data['id_role'];
            $_SESSION['nama_role'] = $data['nama_role'];
            $_SESSION['login_status'] = true;

            // 11. Arahkan user ke dashboard sesuai rolenya (disesuaikan dengan nama role di db baru)
            switch (strtolower($data['nama_role'])) {
                case 'owner': header("Location: admin/dashboard_owner.php"); break;
                case 'admin': header("Location: admin/dashboard_admin.php"); break;
                case 'kasir': header("Location: admin/dashboard_kasir.php"); break;
                // Sesuaikan 'staf gudang' dengan nama role di database-mu jika berbeda
                case 'staf gudang': header("Location: admin/dashboard_gudang.php"); break; 
                default:
                    $_SESSION['error_message'] = 'Role tidak valid.';
                    header("Location: index.php");
            }
            exit;

        } else {
            // PERUBAHAN 6: Update pesan error
            $_SESSION['error_message'] = "Username atau password salah.";
            header("Location: index.php");
            exit;
        }

    } else {
        $_SESSION['error_message'] = 'Akun Anda telah dinonaktifkan.';
        header("Location: index.php");
        exit;
    }

} else {
    // PERUBAHAN 7: Update pesan error
    $_SESSION['error_message'] = 'Username tidak terdaftar.';
    header("Location: index.php");
    exit;
}

$stmt->close();
$koneksi->close();
?>

