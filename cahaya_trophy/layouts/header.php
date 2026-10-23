<?php
// =================================================================
// 1. LOGIKA SEARCH BAR DINAMIS
// =================================================================
$current_url = $_SERVER['REQUEST_URI'];
$search_action = ""; 
$search_placeholder = "Pencarian...";
$hide_search = false;

if (strpos($current_url, 'customers') !== false) {
    $search_action = "index.php";
    $search_placeholder = "Cari pelanggan...";
} elseif (strpos($current_url, 'inventory') !== false) {
    $search_action = "index.php";
    $search_placeholder = "Cari stok bahan baku...";
} elseif (strpos($current_url, 'catalog') !== false) {
    $search_action = "index.php";
    $search_placeholder = "Cari master produk...";
} elseif (strpos($current_url, 'orders') !== false) {
    $search_action = "index.php";
    $search_placeholder = "Cari No Order...";
} else {
    $hide_search = true;
}

// =================================================================
// 2. LOGIKA NOTIFIKASI DINAMIS (STOK KRITIS & DEADLINE)
// =================================================================
$notif_count = 0;
$notif_items = [];

// Pastikan koneksi database tersedia sebelum melakukan query
if (isset($koneksi)) {
    // A. Cek Stok Bahan Baku Kritis
    $q_notif_stok = mysqli_query($koneksi, "SELECT Nama_Bahan, Jumlah_Stok, Satuan FROM bahanbaku WHERE Jumlah_Stok <= Batas_Kritis");
    if ($q_notif_stok) {
        while($stok = mysqli_fetch_assoc($q_notif_stok)) {
            $notif_items[] = [
                'pesan' => "Stok <b>{$stok['Nama_Bahan']}</b> menipis (Sisa: {$stok['Jumlah_Stok']} {$stok['Satuan']})",
                'icon'  => 'bi-box-seam',
                'warna' => 'text-danger',
                'link'  => '../inventory/index.php'
            ];
            $notif_count++;
        }
    }

    // B. Cek Pesanan Mendekati Deadline (Hari ini, Besok, atau Terlewat)
    $hari_ini_notif = date('Y-m-d');
    $besok_notif = date('Y-m-d', strtotime('+1 days'));
    
    $q_notif_deadline = mysqli_query($koneksi, "SELECT NO_PESANAN, Deadline FROM pesanan WHERE Status_Pesanan NOT IN ('Selesai', 'Siap Diambil') AND Deadline <= '$besok_notif'");
    if ($q_notif_deadline) {
        while($order = mysqli_fetch_assoc($q_notif_deadline)) {
            // Beri teks berbeda jika sudah terlewat
            $status_waktu = ($order['Deadline'] < $hari_ini_notif) ? '<span class="text-danger">Terlambat!</span>' : 'Mendekati deadline';
            
            $notif_items[] = [
                'pesan' => "Pesanan <b>#{$order['NO_PESANAN']}</b> $status_waktu (".date('d M Y', strtotime($order['Deadline'])).")",
                'icon'  => 'bi-clock-history',
                'warna' => 'text-warning',
                'link'  => '../orders/detail.php?id=' . $order['NO_PESANAN']
            ];
            $notif_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Cahaya Trophy System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Reset Dasar */
        body { 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            overflow-x: hidden; 
        }
        
        /* Memaksa penimpaan CSS bawaan sidebar.php */
        #sidebar {
            width: 250px !important;
            height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            z-index: 1045 !important;
            background-color: #fff !important;
            transition: transform 0.3s ease-in-out !important;
        }

        /* Struktur Konten Utama */
        .main-content { 
            margin-left: 250px; 
            padding: 20px; 
            min-height: 100vh;
            display: flex; 
            flex-direction: column; 
            transition: margin-left 0.3s ease-in-out;
        }

        /* Header Melayang (Sticky) */
        .top-header { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #eee; 
            padding: 15px 25px; 
            position: sticky; 
            top: 0; 
            z-index: 1030; 
        }

        /* Efek Hover untuk Profil */
        .profile-link:hover .profile-avatar {
            transform: scale(1.08);
            box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.25) !important;
        }
        .profile-avatar {
            transition: all 0.2s ease-in-out;
        }

        /* Latar Belakang Gelap saat Menu HP Dibuka */
        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        /* Scrollbar kustom untuk notifikasi */
        .notif-scroll::-webkit-scrollbar { width: 6px; }
        .notif-scroll::-webkit-scrollbar-thumb { background-color: #dee2e6; border-radius: 10px; }

        /* --- MODE RESPONSIF (TABLET & HP) --- */
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%) !important;
            }
            #sidebar.active {
                transform: translateX(0) !important;
            }
            .main-content { 
                margin-left: 0 !important; 
                padding: 15px; 
            }
            .sidebar-overlay.active { 
                display: block; 
                opacity: 1; 
            }
        }

        @media print {
            #sidebar, .top-header, .d-print-none { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    
    <div class="top-header rounded-4 mb-4 shadow-sm d-print-none d-flex justify-content-between align-items-center">
        
        <div class="d-flex align-items-center flex-grow-1 gap-3">
            <button class="btn btn-light d-lg-none border-0 shadow-sm" id="hamburgerBtn">
                <i class="bi bi-list fs-4"></i>
            </button>

            <div class="w-100 d-none d-md-block" style="max-width: 400px;">
                <?php if(!$hide_search): ?>
                <form action="<?= $search_action ?>" method="GET" class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" class="form-control bg-light border-0 ps-5 rounded-pill" placeholder="<?= $search_placeholder ?>" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </form>
                <?php else: ?>
                    <div class="text-muted fw-medium"><i class="bi bi-calendar3 me-2"></i> <?= date('l, d F Y') ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            
            <div class="dropdown">
                <button class="btn btn-light position-relative rounded-circle d-flex align-items-center justify-content-center border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px;">
                    <i class="bi bi-bell fs-5 text-muted"></i>
                    
                    <?php if($notif_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-top: 8px; margin-left: -10px;">
                        <span class="visually-hidden">Notifikasi Baru</span>
                    </span>
                    <?php endif; ?>
                </button>
                
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 notif-scroll" style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <li class="px-4 py-3 border-bottom bg-light rounded-top-4 d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Notifikasi</span>
                        <?php if($notif_count > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $notif_count ?> Baru</span>
                        <?php endif; ?>
                    </li>
                    
                    <?php if($notif_count > 0): ?>
                        <?php foreach($notif_items as $notif): ?>
                        <li>
                            <a class="dropdown-item py-3 px-4 border-bottom d-flex gap-3 align-items-start text-wrap text-decoration-none" href="<?= $notif['link'] ?>">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center p-2 mt-1">
                                    <i class="bi <?= $notif['icon'] ?> <?= $notif['warna'] ?> fs-5"></i>
                                </div>
                                <div>
                                    <span class="d-block small text-dark lh-sm"><?= $notif['pesan'] ?></span>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-bell-slash fs-3 d-block mb-2 opacity-50"></i>
                                <span class="small fw-medium">Tidak ada notifikasi mendesak.</span>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php 
                // Tentukan link tujuan. Hanya Super Admin yang bisa buka halaman pengaturan
                $link_profil = (isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator') ? '../settings/index.php' : '#'; 
            ?>
            <a href="<?= $link_profil ?>" class="profile-link d-flex align-items-center gap-2 border-start ps-3 text-decoration-none" <?= (isset($_SESSION['role']) && $_SESSION['role'] != 'Super Administrator') ? 'data-bs-toggle="tooltip" data-bs-placement="left" title="Hanya Owner yang dapat mengakses pengaturan"' : '' ?>>
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold text-dark small" style="line-height: 1;"><?= $_SESSION['nama_admin'] ?? 'Admin' ?></div>
                    <div class="text-muted" style="font-size: 0.65rem; text-transform: uppercase;"><?= $_SESSION['role'] ?? 'Administrator' ?></div>
                </div>
                <div class="profile-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px;">
                    <?= strtoupper(substr($_SESSION['nama_admin'] ?? 'CT', 0, 2)) ?>
                </div>
            </a>
        </div>

    </div>