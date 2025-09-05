<?php
// Pastikan session sudah dimulai di halaman utama yang meng-include file ini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PERUBAHAN 1: Ambil 'nama_role' dari session baru kita
$user_role = strtolower($_SESSION['nama_role'] ?? 'guest');
$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi ini sudah bagus, kita pertahankan
function is_active($pages, $current_page) {
    if (in_array($current_page, $pages)) {
        return 'active';
    }
    return '';
}
?>
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
            <!-- Ganti href ke dashboard utama & sesuaikan logo jika perlu -->
            <a href="dashboard_admin.php" class="logo">
                <img src="assets/img/logo/logo_resto.png" alt="Logo Resto" class="navbar-brand" height="30" />
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">

                <!-- ======================================================= -->
                <!-- 1. Menu Dashboard                                       -->
                <!-- ======================================================= -->
                <li class="nav-item <?= is_active(['dashboard.php'], $current_page) ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- ======================================================= -->
                <!-- PERUBAHAN 2: Grup menu disesuaikan untuk sistem resto   -->
                <!-- ======================================================= -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Operasional Harian</h4>
                </li>
                
                <li class="nav-item <?= is_active(['penerimaan.php'], $current_page) ?>">
                    <a href="penerimaan.php">
                        <i class="fas fa-truck-loading"></i>
                        <p>Penerimaan Barang</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['produksi.php'], $current_page) ?>">
                    <a href="produksi.php">
                        <i class="fas fa-blender"></i>
                        <p>Produksi Bumbu</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['permintaan.php'], $current_page) ?>">
                    <a href="permintaan.php">
                        <i class="fas fa-clipboard-list"></i>
                        <p>Permintaan Barang</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['pengeluaran.php'], $current_page) ?>">
                    <a href="pengeluaran.php">
                        <i class="fas fa-dolly-flatbed"></i>
                        <p>Pengeluaran Barang</p>
                    </a>
                </li>
                 <li class="nav-item <?= is_active(['stok_opname.php'], $current_page) ?>">
                    <a href="stok_opname.php">
                        <i class="fas fa-tasks"></i>
                        <p>Penyesuaian Stok</p>
                    </a>
                </li>

                <!-- ======================================================= -->
                <!-- 3. Grup Menu Laporan                                    -->
                <!-- ======================================================= -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Laporan</h4>
                </li>
                <li class="nav-item <?= is_active(['laporan_stok.php'], $current_page) ?>">
                    <a href="laporan_stok.php">
                        <i class="fas fa-chart-bar"></i>
                        <p>Laporan Stok</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['laporan_pemakaian.php'], $current_page) ?>">
                    <a href="laporan_pemakaian.php">
                        <i class="fas fa-chart-pie"></i>
                        <p>Laporan Pemakaian</p>
                    </a>
                </li>

                <!-- ======================================================= -->
                <!-- 4. Grup Menu Master Data (SUDAH DIPERBAIKI)           -->
                <!-- ======================================================= -->
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Master Data</h4>
                </li>
                
                <li class="nav-item <?= is_active(['master_divisi.php'], $current_page) ?>">
                    <a href="master_divisi.php"><i class="fas fa-building"></i><p>Master Divisi</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_role.php'], $current_page) ?>">
                    <a href="master_role.php"><i class="fas fa-user-tag"></i><p>Master Role</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_karyawan.php'], $current_page) ?>">
                    <a href="master_karyawan.php"><i class="fas fa-users-cog"></i><p>Master Karyawan</p></a>
                </li>
                </ul>
        </div>
    </div>
</div>
