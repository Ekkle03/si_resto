<?php
// Pastikan session sudah dimulai di halaman utama yang meng-include file ini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Ambil nama file dan nama folder tempat script dieksekusi
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// 2. Trik Path Dinamis Cerdas
// Jika file yang memanggil sidebar ini ada di dalam sub-folder (contoh: folder 'laporan'),
// maka kita harus naik satu tingkat ('../') untuk ke folder 'admin'.
if ($current_dir == 'laporan') {
    $base_url  = "../";
    $asset_url = "../assets/";
} else {
    // Jika dipanggil dari luar (contoh: dashboard.php, pembelian.php)
    $base_url  = "";
    $asset_url = "assets/";
}

// Ambil 'nama_role' dari session
$user_role = strtolower($_SESSION['nama_role'] ?? 'guest');

// Fungsi active menu
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
            <a href="<?= $base_url ?>dashboard.php" class="logo">
                <img src="<?= $asset_url ?>img/logo/logo_resto.png" alt="Logo Resto" class="navbar-brand" height="30" />
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

                <li class="nav-item <?= is_active(['dashboard.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Operasional Harian</h4>
                </li>

                <?php if (in_array($user_role, ['admin', 'owner', 'purchasing'])): ?>
                <li class="nav-item <?= is_active(['pembelian.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>pembelian.php">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Pembelian</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['penerimaan.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>penerimaan.php">
                        <i class="fas fa-boxes"></i>
                        <p>Penerimaan Barang</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($user_role, ['admin', 'owner', 'staf'])): ?>
                <li class="nav-item <?= is_active(['produksi.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>produksi.php">
                        <i class="fas fa-blender"></i>
                        <p>Produksi 1 (BSJ)</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['produksi_langsung.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>produksi_langsung.php">
                        <i class="fas fa-fire"></i>
                        <p>Produksi 2 (Menu)</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item <?= is_active(['permintaan_bahan.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>permintaan_bahan.php">
                        <i class="fas fa-clipboard-list"></i>
                        <p>Permintaan Barang</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['waste.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>waste.php">
                        <i class="fas fa-trash-alt"></i>
                        <p>Pencatatan Waste</p>
                    </a>
                </li>

                <?php if (in_array($user_role, ['admin', 'owner', 'staf'])): ?>
                <li class="nav-item <?= is_active(['menu_terjual.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>menu_terjual.php">
                        <i class="fas fa-concierge-bell"></i>
                        <p>Menu Terjual</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item <?= is_active(['stok_opname.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>stok_opname.php">
                        <i class="fas fa-clipboard-check"></i>
                        <p>Stok Opname</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Laporan</h4>
                </li>
                
                <li class="nav-item <?= is_active(['stok_bahan.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>stok_bahan.php">
                        <i class="fas fa-layer-group"></i>
                        <p>Kartu Stok</p>
                    </a>
                </li>

                <?php if (in_array($user_role, ['admin', 'owner'])): ?>
                <li class="nav-item <?= is_active(['laporan_waste.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>laporan/laporan_waste.php">
                        <i class="fas fa-chart-bar"></i>
                        <p>Laporan Waste</p>
                    </a>
                </li>
                <li class="nav-item <?= is_active(['laporan_bom.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>laporan/laporan_bom.php">
                        <i class="fas fa-chart-pie"></i>
                        <p>Laporan BOM</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($user_role, ['admin', 'owner'])): ?>
                <li class="nav-section">
                    <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                    <h4 class="text-section">Master Data</h4>
                </li>
                <li class="nav-item <?= is_active(['master_bom.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_bom.php"><i class="fas fa-sitemap"></i><p>Master BOM</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_divisi.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_divisi.php"><i class="fas fa-building"></i><p>Master Divisi</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_role.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_role.php"><i class="fas fa-user-shield"></i><p>Master Role</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_karyawan.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_karyawan.php"><i class="fas fa-users-cog"></i><p>Master Karyawan</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_satuan.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_satuan.php"><i class="fas fa-balance-scale"></i><p>Master Satuan</p></a>
                </li>
                <li class="nav-item <?= is_active(['konversi_satuan.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>konversi_satuan.php"><i class="fas fa-exchange-alt"></i><p>Konversi Satuan</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_kategori.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_kategori.php"><i class="fas fa-tags"></i><p>Master Kategori</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_bahanbaku.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_bahanbaku.php"> <i class="fas fa-seedling"></i><p>Master Bahan Baku</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_bahan_setengahjadi.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_bahan_setengahjadi.php"><i class="fas fa-box-open"></i><p>Master Bahan Stg Jadi</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_menu.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_menu.php"><i class="fas fa-utensils"></i><p>Master Menu</p></a>
                </li>
                <li class="nav-item <?= is_active(['master_gudang.php'], $current_page) ?>">
                    <a href="<?= $base_url ?>master_gudang.php"><i class="fas fa-store-alt"></i><p>Master Gudang</p></a>
                </li>
                <?php endif; ?>
                
            </ul>
        </div>
    </div>
</div>