<?php
// Mulai session
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// ===== Guard: hanya POST =====
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_karyawan.php");
    exit();
}

// ===== Ambil & bersihkan input =====
$id_karyawan     = isset($_POST['id_karyawan']) ? (int)$_POST['id_karyawan'] : 0;
$nama_lengkap    = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
$telepon         = isset($_POST['telepon']) ? trim($_POST['telepon']) : null;
$alamat          = isset($_POST['alamat']) ? trim($_POST['alamat']) : null;
$id_divisi       = isset($_POST['id_divisi']) ? (int)$_POST['id_divisi'] : 0;
$id_role         = (isset($_POST['id_role']) && $_POST['id_role'] !== '') ? (int)$_POST['id_role'] : null; // opsional
$password_raw    = isset($_POST['password']) ? (string)$_POST['password'] : ''; // opsional
$old_foto_profil = isset($_POST['old_foto_profil']) ? trim($_POST['old_foto_profil']) : 'default.png';

// Validasi minimal
if ($id_karyawan <= 0 || $nama_lengkap === '' || $id_divisi === 0) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Data wajib tidak lengkap."));
    exit();
}

// ===== Path upload =====
$upload_dir_fs  = __DIR__ . '/assets/img/profil';   // filesystem
if (!is_dir($upload_dir_fs)) {
    if (!@mkdir($upload_dir_fs, 0755, true)) {
        header("Location: master_karyawan.php?msg=" . urlencode("Error: Folder upload tidak bisa dibuat."));
        exit();
    }
}
if (!is_writable($upload_dir_fs)) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Folder upload tidak writable."));
    exit();
}

// ===== Upload foto (opsional) =====
$new_foto_name = null; // jika user upload baru, ini akan terisi
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

        // Validasi mime
        $fi   = new finfo(FILEINFO_MIME_TYPE);
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

        // Nama file unik & pindahkan
        $ext          = $allowed[$mime];
        $new_foto_name = 'profil_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . $ext;
        $target        = $upload_dir_fs . '/' . $new_foto_name;

        if (!@move_uploaded_file($tmp, $target)) {
            header("Location: master_karyawan.php?msg=" . urlencode("Error: Gagal menyimpan file upload."));
            exit();
        }
        if (!file_exists($target)) {
            header("Location: master_karyawan.php?msg=" . urlencode("Error: File upload tidak ditemukan setelah dipindahkan."));
            exit();
        }
    } else {
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

// ===== Hash password jika diisi =====
$password_hashed = null;
if ($password_raw !== '') {
    $password_hashed = password_hash($password_raw, PASSWORD_BCRYPT);
    if ($password_hashed === false) {
        header("Location: master_karyawan.php?msg=" . urlencode("Error: Gagal meng-hash password."));
        exit();
    }
}

// ===== Transaksi update =====
mysqli_begin_transaction($koneksi);

try {
    // 4 CABANG agar binding rapi

    if ($new_foto_name && $password_hashed !== null) {
        // Foto + Password
        $sql = "UPDATE master_karyawan
                SET nama_lengkap = ?, telepon = ?, alamat = ?, id_divisi = ?, id_role = ?, foto_profil = ?, password = ?
                WHERE id_karyawan = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if (!$stmt) throw new Exception('Gagal menyiapkan query (foto+pass).');
        mysqli_stmt_bind_param(
            $stmt,
            "sssii ssi",
            $nama_lengkap,
            $telepon,
            $alamat,
            $id_divisi,
            $id_role,
            $new_foto_name,
            $password_hashed,
            $id_karyawan
        );

    } elseif ($new_foto_name && $password_hashed === null) {
        // Hanya Foto
        $sql = "UPDATE master_karyawan
                SET nama_lengkap = ?, telepon = ?, alamat = ?, id_divisi = ?, id_role = ?, foto_profil = ?
                WHERE id_karyawan = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if (!$stmt) throw new Exception('Gagal menyiapkan query (foto).');
        mysqli_stmt_bind_param(
            $stmt,
            "sssiisi",
            $nama_lengkap,
            $telepon,
            $alamat,
            $id_divisi,
            $id_role,
            $new_foto_name,
            $id_karyawan
        );

    } elseif (!$new_foto_name && $password_hashed !== null) {
        // Hanya Password
        $sql = "UPDATE master_karyawan
                SET nama_lengkap = ?, telepon = ?, alamat = ?, id_divisi = ?, id_role = ?, password = ?
                WHERE id_karyawan = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if (!$stmt) throw new Exception('Gagal menyiapkan query (pass).');
        mysqli_stmt_bind_param(
            $stmt,
            "sssiisi",
            $nama_lengkap,
            $telepon,
            $alamat,
            $id_divisi,
            $id_role,
            $password_hashed,
            $id_karyawan
        );

    } else {
        // Tanpa Foto & Tanpa Password
        $sql = "UPDATE master_karyawan
                SET nama_lengkap = ?, telepon = ?, alamat = ?, id_divisi = ?, id_role = ?
                WHERE id_karyawan = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if (!$stmt) throw new Exception('Gagal menyiapkan query (basic).');
        mysqli_stmt_bind_param(
            $stmt,
            "sssiii",
            $nama_lengkap,
            $telepon,
            $alamat,
            $id_divisi,
            $id_role,
            $id_karyawan
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal mengupdate data karyawan: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    // Hapus foto lama jika ada foto baru (kecuali default.png)
    if ($new_foto_name) {
        $old = basename($old_foto_profil ?: 'default.png');
        if ($old !== 'default.png') {
            $old_path = $upload_dir_fs . '/' . $old;
            if (is_file($old_path)) {
                @unlink($old_path);
            }
        }
    }

    mysqli_commit($koneksi);
    header("Location: master_karyawan.php?msg=" . urlencode("Data karyawan berhasil diupdate."));
    exit();

} catch (Throwable $e) {
    mysqli_rollback($koneksi);

    // Rollback file baru kalau ada
    if ($new_foto_name) {
        $new_path = $upload_dir_fs . '/' . $new_foto_name;
        if (is_file($new_path)) @unlink($new_path);
    }

    $msg = 'Error: ' . $e->getMessage();
    header("Location: master_karyawan.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
