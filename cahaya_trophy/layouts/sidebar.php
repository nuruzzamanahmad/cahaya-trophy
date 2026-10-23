<style>
    .sidebar { width: 250px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 1040; background-color: #fff; border-right: 1px solid #eaeaea; box-shadow: 2px 0 10px rgba(0,0,0,0.02); }
    .sidebar .nav-link { color: #6c757d; font-weight: 500; padding: 12px 20px; border-radius: 0; margin-bottom: 5px; transition: all 0.2s; border-left: 4px solid transparent; }
    .sidebar .nav-link:hover { color: #0d6efd; background-color: rgba(13, 110, 253, 0.05); }
    .sidebar .nav-link.active { color: #0d6efd; background-color: rgba(13, 110, 253, 0.1); border-left: 4px solid #0d6efd; font-weight: 600; }
    .sidebar .nav-link i { font-size: 1.2rem; width: 30px; display: inline-block; vertical-align: middle; }
</style>

<div class="sidebar d-flex flex-column d-print-none" id="sidebar">
    <div class="sidebar-brand d-flex flex-column justify-content-center px-4 py-4 border-bottom">
        <h5 class="fw-bold text-primary mb-0">Cahaya Trophy</h5>
        <span class="text-muted" style="font-size: 0.65rem; letter-spacing: 1px;">MANAGEMENT SYSTEM</span>
    </div>
    
    <ul class="nav flex-column mt-3 px-0 mb-auto">
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>" href="../dashboard/index.php">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'orders') !== false ? 'active' : '' ?>" href="../orders/index.php">
                <i class="bi bi-cart3"></i> Orders
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'inventory') !== false ? 'active' : '' ?>" href="../inventory/index.php">
                <i class="bi bi-box-seam"></i> Inventory
            </a>
        </li>

        <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'Super Administrator' || $_SESSION['role'] == 'Kasir')): ?>
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'catalog') !== false ? 'active' : '' ?>" href="../catalog/index.php">
                <i class="bi bi-journal-album"></i> Master Produk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'customers') !== false ? 'active' : '' ?>" href="../customers/index.php">
                <i class="bi bi-people"></i> Customers
            </a>
        </li>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator'): ?>
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : '' ?>" href="../reports/index.php">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="px-0 pb-4 pt-3 border-top mt-auto">
        <ul class="nav flex-column">
            
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator'): ?>
            <li class="nav-item">
                <a class="nav-link text-muted <?= strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'active' : '' ?>" href="../settings/index.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item mt-2">
                <a class="nav-link text-danger fw-bold" href="../logout.php">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>