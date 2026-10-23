<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

// Proteksi Keamanan: Wajib login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Daftar Pesanan - Cahaya Trophy";

// ==========================================
// 1. LOGIKA FILTER, PENCARIAN, SORTING & PAGINASI
// ==========================================
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Semua';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Sorting
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'p.NO_PESANAN';
$order = isset($_GET['order']) && $_GET['order'] == 'asc' ? 'ASC' : 'DESC';

// Paginasi
$limit = 15; // Tampilkan 15 pesanan per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where_conditions = [];

if ($filter_status != 'Semua') {
    $where_conditions[] = "p.Status_Pesanan = '$filter_status'";
}

if (!empty($search)) {
    $where_conditions[] = "(p.NO_PESANAN LIKE '%$search%' OR pel.Nama_Pelanggan LIKE '%$search%')";
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Menghitung total data untuk paginasi
$q_count = mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM pesanan p 
    JOIN pelanggan pel ON p.ID_PELANGGAN = pel.ID_PELANGGAN 
    $where_clause
");
$total_data = mysqli_fetch_assoc($q_count)['total'];
$total_pages = ceil($total_data / $limit);

// Query Utama (Dengan limit)
$query_pesanan = mysqli_query($koneksi, "
    SELECT p.NO_PESANAN, pel.Nama_Pelanggan, p.Deadline, p.Total_Harga, p.Sisa_Tagihan, p.Status_Pesanan,
           (SELECT Kategori_Produk FROM detail_pesanan dp WHERE dp.NO_PESANAN = p.NO_PESANAN LIMIT 1) as Produk
    FROM pesanan p
    JOIN pelanggan pel ON p.ID_PELANGGAN = pel.ID_PELANGGAN
    $where_clause
    ORDER BY $sort_by $order
    LIMIT $offset, $limit
");

// ==========================================
// FUNGSI HELPER UNTUK SORTING TABEL
// ==========================================
function sortUrl($column, $current_sort, $current_order, $filter_status, $search) {
    $new_order = ($current_sort == $column && $current_order == 'DESC') ? 'asc' : 'desc';
    $url = "?sort_by=$column&order=$new_order&status=" . urlencode($filter_status);
    if (!empty($search)) $url .= "&search=" . urlencode($search);
    return $url;
}

function sortIcon($column, $current_sort, $current_order) {
    if ($current_sort == $column) {
        return $current_order == 'asc' ? ' <i class="bi bi-caret-up-fill small"></i>' : ' <i class="bi bi-caret-down-fill small"></i>';
    }
    return '';
}

// ==========================================
// 2. LOGIKA KARTU RINGKASAN BAWAH
// ==========================================
$q_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE Status_Pesanan = 'Menunggu DP'"));
$q_produksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE Status_Pesanan IN ('Diproses', 'Produksi')"));
$q_siap = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE Status_Pesanan = 'Siap Diambil'"));
$q_omzet = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(Total_Harga) as total FROM pesanan WHERE Status_Pesanan = 'Selesai'"));

include '../layouts/header.php';
$hari_ini = date('Y-m-d');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0 text-primary">Daftar Pesanan</h4>
    <div class="d-flex gap-2">
        <?php if($_SESSION['role'] == 'Super Administrator'): ?>
        <a href="export.php?status=<?= urlencode($filter_status) ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-success fw-medium px-3">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'Super Administrator' || $_SESSION['role'] == 'Kasir'): ?>
        <a href="tambah.php" class="btn btn-primary fw-medium px-3">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pesanan
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Data pesanan berhasil disimpan!
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif(isset($_GET['pesan']) && $_GET['pesan'] == 'dihapus'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2" role="alert">
        <i class="bi bi-trash-fill me-2"></i> Data pesanan berhasil dihapus!
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(!empty($search)): ?>
    <div class="mb-3 text-muted small">
        Menampilkan hasil pencarian untuk: <span class="fw-bold text-dark">"<?= htmlspecialchars($search) ?>"</span>
        <a href="index.php" class="text-danger text-decoration-none ms-2"><i class="bi bi-x-circle"></i> Reset Pencarian</a>
    </div>
<?php endif; ?>

<div class="d-flex gap-2 overflow-auto pb-2 mb-3 no-scrollbar">
    <?php 
    $daftar_status = ['Semua', 'Menunggu DP', 'Diproses', 'Produksi', 'Siap Diambil', 'Selesai'];
    foreach($daftar_status as $status) : 
        $active_class = ($filter_status == $status) ? 'btn-primary' : 'btn-light border text-muted';
    ?>
        <a href="?status=<?= urlencode($status) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn <?= $active_class ?> rounded-pill px-4 fw-medium" style="white-space: nowrap; font-size: 0.9rem;">
            <?= $status ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted" style="font-size: 0.85rem;">
                    <tr>
                        <th class="ps-4 py-3"><a href="<?= sortUrl('p.NO_PESANAN', $sort_by, $order, $filter_status, $search) ?>" class="text-dark text-decoration-none">No Pesanan<?= sortIcon('p.NO_PESANAN', $sort_by, $order) ?></a></th>
                        <th><a href="<?= sortUrl('pel.Nama_Pelanggan', $sort_by, $order, $filter_status, $search) ?>" class="text-dark text-decoration-none">Pelanggan<?= sortIcon('pel.Nama_Pelanggan', $sort_by, $order) ?></a></th>
                        <th>Produk</th>
                        <th><a href="<?= sortUrl('p.Deadline', $sort_by, $order, $filter_status, $search) ?>" class="text-dark text-decoration-none">Deadline<?= sortIcon('p.Deadline', $sort_by, $order) ?></a></th>
                        <th><a href="<?= sortUrl('p.Total_Harga', $sort_by, $order, $filter_status, $search) ?>" class="text-dark text-decoration-none">Total Harga<?= sortIcon('p.Total_Harga', $sort_by, $order) ?></a></th>
                        <th>Sisa Tagihan</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.9rem;">
                    <?php 
                    if (mysqli_num_rows($query_pesanan) > 0) {
                        while($row = mysqli_fetch_assoc($query_pesanan)) { 
                            $is_overdue = ($row['Deadline'] < $hari_ini && $row['Status_Pesanan'] != 'Selesai');
                    ?>
                    <tr class="<?= $is_overdue ? 'bg-danger bg-opacity-10' : '' ?>">
                        <td class="ps-4 text-primary fw-medium">#<?= $row['NO_PESANAN'] ?></td>
                        <td class="fw-medium text-dark"><?= $row['Nama_Pelanggan'] ?></td>
                        <td class="text-muted" style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= $row['Produk'] ? $row['Produk'] : '-' ?>
                        </td>
                        <td>
                            <?php if($is_overdue): ?>
                                <span class="badge bg-danger rounded-pill px-2 py-1 blink-text" title="Pesanan Terlambat!"><i class="bi bi-exclamation-triangle-fill"></i> <?= formatTanggal($row['Deadline']) ?></span>
                            <?php else: ?>
                                <span class="<?= ($row['Status_Pesanan'] != 'Selesai') ? 'text-dark fw-medium' : 'text-muted' ?>">
                                    <?= formatTanggal($row['Deadline']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatRupiah($row['Total_Harga']) ?></td>
                        <td>
                            <?php if($row['Sisa_Tagihan'] > 0): ?>
                                <span class="text-danger fw-bold"><?= formatRupiah($row['Sisa_Tagihan']) ?></span>
                            <?php else: ?>
                                <span class="text-success fw-medium"><i class="bi bi-check-circle"></i> Lunas</span>
                            <?php endif; ?>
                        </td>
                        <td><?= badgeStatusPesanan($row['Status_Pesanan']) ?></td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="detail.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-sm btn-light text-primary" data-bs-toggle="tooltip" title="Detail"><i class="bi bi-eye"></i></a>
                                
                                <?php if($_SESSION['role'] == 'Super Administrator' || $_SESSION['role'] == 'Kasir'): ?>
                                <a href="edit.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-sm btn-light text-secondary" data-bs-toggle="tooltip" title="Update Status">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>

                                <?php if($_SESSION['role'] == 'Super Administrator'): ?>
                                <a href="hapus.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-sm btn-light text-danger btn-hapus" data-pesan="Pesanan <?= $row['NO_PESANAN'] ?>" data-bs-toggle="tooltip" title="Hapus"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted py-5">Belum ada data pesanan yang sesuai.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if($total_pages > 1): ?>
        <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan <?= mysqli_num_rows($query_pesanan) ?> dari <?= $total_data ?> pesanan</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php 
                            $page_url = "?page=$i&sort_by=$sort_by&order=$order&status=" . urlencode($filter_status);
                            if (!empty($search)) $page_url .= "&search=" . urlencode($search);
                        ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $page_url ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-clipboard-data fs-5"></i></div>
                <div>
                    <div class="text-muted small fw-medium text-uppercase">Pending</div>
                    <div class="fs-4 fw-bold text-dark"><?= $q_pending['total'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background-color: #fff4e6;">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: #fd7e14;"><i class="bi bi-hammer fs-5"></i></div>
                <div>
                    <div class="text-muted small fw-medium text-uppercase">Produksi</div>
                    <div class="fs-4 fw-bold text-dark"><?= $q_produksi['total'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-info bg-opacity-10 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-truck fs-5"></i></div>
                <div>
                    <div class="text-muted small fw-medium text-uppercase">Siap Ambil</div>
                    <div class="fs-4 fw-bold text-dark"><?= $q_siap['total'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if($_SESSION['role'] == 'Super Administrator'): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-success bg-opacity-10 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-cash-stack fs-5"></i></div>
                <div>
                    <div class="text-muted small fw-medium text-uppercase">Omzet Total</div>
                    <div class="fs-5 fw-bold text-dark"><?= formatRupiahSingkat($q_omzet['total'] ? $q_omzet['total'] : 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    @keyframes blink { 50% { opacity: 0.5; } }
    .blink-text { animation: blink 1.5s linear infinite; }
</style>

<?php include '../layouts/footer.php'; ?>