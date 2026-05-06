<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

$id_login = $_SESSION['id_karyawan'] ?? $_SESSION['id_user'] ?? null;

if (!$id_login) {
    header("Location: ../login.php");
    exit();
}

// PERBAIKAN: Sesuaikan nama kolom dengan struktur tabel master_karyawan
$sql = "SELECT k.*, r.nama_role 
        FROM master_karyawan k
        LEFT JOIN master_role r ON k.id_role = r.id_role
        WHERE k.id_karyawan = '$id_login'";

$query = mysqli_query($koneksi, $sql);
$user  = mysqli_fetch_assoc($query);

// PERBAIKAN: Nama kolom disesuaikan dengan DB (nama_lengkap, telepon, foto_profil)
$nama     = $user['nama_lengkap'] ?? 'Data tidak ditemukan';
$username = $user['username']     ?? '-';
$role     = $user['nama_role']    ?? '-';
$telp     = $user['telepon']      ?? '-';
$alamat   = $user['alamat']       ?? '-';
$foto_db  = $user['foto_profil']  ?? '';

$foto_path = (!empty($foto_db)) ? 'assets/img/profil/' . $foto_db : 'assets/img/profil/default.png';

// ── Navbar: siapkan variabel session ──────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil'])
            ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : 'assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Profile - SI Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .profile-container { max-width: 860px; margin: 30px auto; }

        .profile-card { border-radius: 20px; border: none; box-shadow: 0 8px 30px rgba(0,0,0,0.08); overflow: hidden; }

        .profile-banner {
            background: linear-gradient(135deg, #1572e8 0%, #6610f2 100%);
            height: 140px;
            position: relative;
        }
        .profile-banner::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 40px;
            background: #fff;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }

        .profile-body { background: #fff; padding: 0 40px 40px 40px; text-align: center; }

        .profile-avatar-wrap { margin-top: -75px; margin-bottom: 16px; position: relative; z-index: 1; }
        .avatar-lg-custom {
            width: 140px; height: 140px;
            border: 5px solid #fff;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            object-fit: cover;
        }

        .profile-name { font-size: 26px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
        .profile-username { color: #8d9498; font-size: 14px; }

        /* Info Cards Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            text-align: left;
            margin-top: 36px;
        }
        .info-card {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 18px 20px;
            border-left: 4px solid #1572e8;
            transition: box-shadow 0.2s;
        }
        .info-card:hover { box-shadow: 0 4px 15px rgba(21,114,232,0.1); }
        .info-card.full-width { grid-column: span 2; }
        .info-card label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #8d9498;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        .info-card label i { color: #1572e8; font-size: 12px; }
        .info-card p { color: #2a2b2d; font-size: 15px; font-weight: 600; margin: 0; }
        .info-card p.text-muted { font-weight: 400; font-size: 14px; }

        .card-footer-custom {
            background: #f8f9fc;
            border-top: 1px solid #eef0f5;
            padding: 14px 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <!-- ── NAVBAR DIPERBAIKI ──────────────────────────────────────── -->
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="<?= $foto ?>"
                                         alt="Foto Profil"
                                         class="avatar-img rounded-circle"
                                         onerror="this.src='assets/img/profil/default.png'" />
                                </div>
                                <span class="profile-username">
                                    <span class="op-7">Selamat Datang,</span>
                                    <span class="fw-bold"><?= $nama ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg">
                                                <img src="<?= $foto ?>"
                                                     alt="Foto Profil"
                                                     class="avatar-img rounded"
                                                     onerror="this.src='assets/img/profil/default.png'" />
                                            </div>
                                            <div class="u-text">
                                                <h4><?= $nama ?></h4>
                                                <p class="text-muted">@<?= $username ?></p>
                                                <?php if (!empty($role)): ?>
                                                    <span class="badge bg-secondary mb-2"><?= $role ?></span>
                                                <?php endif; ?>
                                                <br>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="../logout.php">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- ── END NAVBAR ─────────────────────────────────────────────── -->
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="profile-container">
                        <div class="card profile-card">

                            <div class="profile-banner"></div>

                            <div class="profile-body">
                                <div class="profile-avatar-wrap">
                                    <img src="<?= $foto_path ?>"
                                         class="avatar-lg-custom rounded-circle"
                                         onerror="this.src='assets/img/profil/default.png'">
                                </div>

                                <div class="profile-name"><?= htmlspecialchars($nama) ?></div>
                                <div class="profile-username mb-2">@<?= htmlspecialchars($username) ?></div>
                                <span class="badge bg-primary px-3 py-2" style="border-radius: 20px; font-size: 12px;">
                                    <i class="fa fa-shield-alt me-1"></i><?= htmlspecialchars($role) ?>
                                </span>

                                <div class="info-grid">
                                    <div class="info-card">
                                        <label><i class="fa fa-phone"></i> Nomor Telepon / WhatsApp</label>
                                        <p><?= htmlspecialchars($telp) ?></p>
                                    </div>
                                    <div class="info-card">
                                        <label><i class="fa fa-briefcase"></i> Jabatan Organisasi</label>
                                        <p><?= htmlspecialchars($role) ?></p>
                                    </div>
                                    <div class="info-card">
                                        <label><i class="fa fa-id-badge"></i> ID Karyawan</label>
                                        <p>#<?= htmlspecialchars($id_login) ?></p>
                                    </div>
                                    <div class="info-card">
                                        <label><i class="fa fa-user"></i> Username</label>
                                        <p>@<?= htmlspecialchars($username) ?></p>
                                    </div>
                                    <div class="info-card full-width">
                                        <label><i class="fa fa-map-marker-alt"></i> Alamat Lengkap</label>
                                        <p class="text-muted"><?= htmlspecialchars($alamat) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer-custom">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>