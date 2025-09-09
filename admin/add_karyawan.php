<?php
// Selalu mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// ===== Helper: sanitasi nama depan jadi slug sederhana =====
function get_firstname_slug($full) {
    $full = trim($full);
    if ($full === '') return '';
    $parts = preg_split('/\s+/', $full);
    $first = strtolower($parts[0]);
    // hilangkan non alnum
    $first = preg_replace('/[^a-z0-9]/', '', $first);
    return $first ?: 'user';
}

// Pastikan request POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_karyawan.php");
    exit();
}

// Ambil & bersihkan input (schema baru: password & id_role ada di master_karyawan)
$nama_lengkap  = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
$telepon       = isset($_POST['telepon']) ? trim($_POST['telepon']) : null;
$alamat        = isset($_POST['alamat']) ? trim($_POST['alamat']) : null;
$id_divisi     = isset($_POST['id_divisi']) ? (int)$_POST['id_divisi'] : 0;
$id_role       = isset($_POST['id_role']) && $_POST['id_role'] !== '' ? (int)$_POST['id_role'] : null; // opsional
$password_raw  = isset($_POST['password']) ? (string)$_POST['password'] : '';

// Validasi minimal
if ($nama_lengkap === '' || $id_divisi === 0 || $password_raw === '') {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Data wajib tidak lengkap (nama, divisi, password)."));
    exit();
}

// Hash password
$password_hashed = password_hash($password_raw, PASSWORD_BCRYPT);
if ($password_hashed === false) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Gagal meng-hash password."));
    exit();
}

// ====== Upload Foto Profil (opsional) ======
$foto_profil    = 'default.png';
$upload_dir_web = 'assets/img/profil';              // URL src
$upload_dir_fs  = __DIR__ . '/assets/img/profil';   // path filesystem (admin/assets/img/profil)

// Pastikan folder ada
if (!is_dir($upload_dir_fs)) {
    if (!@mkdir($upload_dir_fs, 0755, true)) {
        header("Location: master_karyawan.php?msg=" . urlencode("Error: Folder upload tidak bisa dibuat: $upload_dir_fs"));
        exit();
    }
}
if (!is_writable($upload_dir_fs)) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Folder upload tidak writable: $upload_dir_fs"));
    exit();
}

if (isset($_FILES['foto_profil']) && isset($_FILES['foto_profil']['error']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
    $err = (int)$_FILES['foto_profil']['error'];

    if ($err === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['foto_profil']['tmp_name'];
        $size = (int)$_FILES['foto_profil']['size'];

        // Batas ukuran 2MB
        $max = 2 * 1024 * 1024;
        if ($size > $max) {
            header("Location: master_karyawan.php?msg=" . urlencode("Error: Ukuran file melebihi 2MB."));
            exit();
        }

        // Validasi mime type
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($tmp);
        $allowed = [
            'image/jpeg' => '.jpg',
            'image/png'  => '.png',
            'image/webp' => '.webp',
        ];
        if (!isset($allowed[$mime])) {
            header("Location: master_karyawan.php?msg=" . urlencode("Error: Format gambar harus JPG/PNG/WEBP."));
            exit();
        }

        // Nama file unik
        $ext = $allowed[$mime];
        $nama_file = 'profil_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . $ext;
        $target = $upload_dir_fs . '/' . $nama_file;

        // Pindahkan
        if (!@move_uploaded_file($tmp, $target)) {
            header("Location: master_karyawan.php?msg=" . urlencode("Error: Gagal menyimpan file upload."));
            exit();
        }
        // Verifikasi
        if (!file_exists($target)) {
            header("Location: master_karyawan.php?msg=" . urlencode("Error: File upload tidak ditemukan setelah dipindahkan."));
            exit();
        }

        $foto_profil = $nama_file;
    } else {
        // Map error upload
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'File melebihi upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas form.',
            UPLOAD_ERR_PARTIAL    => 'File terupload sebagian.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder tmp tidak ada.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload diblok ekstensi PHP.',
        ];
        $pesan = isset($map[$err]) ? $map[$err] : 'Gagal upload file.';
        header("Location: master_karyawan.php?msg=" . urlencode("Error: $pesan (code: $err)"));
        exit();
    }
}

// Fallback ekstra
if (empty($foto_profil)) { $foto_profil = 'default.png'; }

// ===== Transaksi agar konsisten =====
mysqli_begin_transaction($koneksi);
try {
    // 1) INSERT awal ke master_karyawan, username NULL (auto di step 2)
    $sql1 = "INSERT INTO master_karyawan (username, nama_lengkap, telepon, alamat, id_divisi, foto_profil, password, id_role)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt1 = mysqli_prepare($koneksi, $sql1);
    if (!$stmt1) throw new Exception('Gagal menyiapkan query 1.');

    $username_null = null; // akan diisi nanti

    mysqli_stmt_bind_param(
        $stmt1,
        "ssssissi",
        $username_null,   // username -> NULL dulu
        $nama_lengkap,
        $telepon,
        $alamat,
        $id_divisi,
        $foto_profil,
        $password_hashed,
        $id_role
    );

    if (!mysqli_stmt_execute($stmt1)) throw new Exception('Gagal menyimpan data karyawan.');
    mysqli_stmt_close($stmt1);

    // 2) Ambil ID baru & generate username
    $new_id = (int)mysqli_insert_id($koneksi);
    $first  = get_firstname_slug($nama_lengkap);
    $username_auto = $first . $new_id; // unik karena ada ID

    $sql2 = "UPDATE master_karyawan SET username = ? WHERE id_karyawan = ?";
    $stmt2 = mysqli_prepare($koneksi, $sql2);
    if (!$stmt2) throw new Exception('Gagal menyiapkan query 2.');
    mysqli_stmt_bind_param($stmt2, "si", $username_auto, $new_id);
    if (!mysqli_stmt_execute($stmt2)) throw new Exception('Gagal mengisi username otomatis.');
    mysqli_stmt_close($stmt2);

    // 3) Commit
    mysqli_commit($koneksi);

    header("Location: master_karyawan.php?msg=" . urlencode("Data karyawan berhasil ditambahkan. Username: $username_auto"));
    exit();

} catch (Throwable $e) {
    // Rollback DB
    mysqli_rollback($koneksi);
    // Hapus file upload jika ada
    if ($foto_profil !== 'default.png') {
        $uploaded = $upload_dir_fs . '/' . basename($foto_profil);
        if (is_file($uploaded)) { @unlink($uploaded); }
    }

    $msg = 'Error: ' . $e->getMessage();
    header("Location: master_karyawan.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
