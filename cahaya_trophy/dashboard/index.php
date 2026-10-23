<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

// Proteksi Keamanan: Wajib login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Dashboard - Cahaya Trophy";

// ==========================================
// 1. PENGAMBILAN DATA DINAMIS DARI DATABASE
// ==========================================

// Hitung Total Pesanan
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan");
$total_pesanan = mysqli_fetch_assoc($q_total)['total'];

// Hitung Pesanan Diproses (Status: Diproses, Produksi)
$q_proses = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE Status_Pesanan IN ('Diproses', 'Produksi', 'Menunggu DP')");
$pesanan_diproses = mysqli_fetch_assoc($q_proses)['total'];

// Hitung Pesanan Selesai
$q_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE Status_Pesanan IN ('Selesai', 'Siap Diambil')");
$pesanan_selesai = mysqli_fetch_assoc($q_selesai)['total'];

// Hitung Total Omzet (Hanya dari pesanan selesai)
$q_omzet = mysqli_query($koneksi, "SELECT SUM(Total_Harga) as total_omzet FROM pesanan WHERE Status_Pesanan IN ('Selesai', 'Siap Diambil')");
$d_omzet = mysqli_fetch_assoc($q_omzet);
$total_omzet = $d_omzet['total_omzet'] ? $d_omzet['total_omzet'] : 0;

// Ambil 3 Pesanan Paling Mendekati Deadline yang belum selesai
$q_deadline = mysqli_query($koneksi, "
    SELECT p.NO_PESANAN, pel.Nama_Pelanggan, p.Deadline, p.Status_Pesanan 
    FROM pesanan p 
    JOIN pelanggan pel ON p.ID_PELANGGAN = pel.ID_PELANGGAN 
    WHERE p.Status_Pesanan NOT IN ('Selesai', 'Diambil') 
    ORDER BY p.Deadline ASC LIMIT 3
");

// Ambil Bahan Baku yang stoknya kritis (deteksi dari batas kritis)
$q_stok = mysqli_query($koneksi, "SELECT ID_BAHAN, Nama_Bahan, Jumlah_Stok, Satuan FROM bahanbaku WHERE Jumlah_Stok <= Batas_Kritis ORDER BY Jumlah_Stok ASC LIMIT 4");


// ==========================================
// 2. PERSIAPAN DATA GRAFIK OMZET (DINAMIS)
// ==========================================
$label_mingguan = [];
$data_mingguan = [];
$label_bulanan = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
$data_bulanan = [0, 0, 0, 0];

if (isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator') {
    
    // A. Data Mingguan (7 Hari Terakhir berdasarkan Deadline/Tgl Selesai)
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label_mingguan[] = date('d M', strtotime($date));
        
        $q_harian = mysqli_query($koneksi, "SELECT SUM(Total_Harga) as total FROM pesanan WHERE Status_Pesanan IN ('Selesai', 'Siap Diambil') AND DATE(Deadline) = '$date'");
        $row_harian = mysqli_fetch_assoc($q_harian);
        $data_mingguan[] = $row_harian['total'] ? (int)$row_harian['total'] : 0;
    }

    // B. Data Bulanan (Pengelompokan 4 Minggu di Bulan Ini)
    $m = date('m');
    $y = date('Y');
    $q_bulanan = mysqli_query($koneksi, "SELECT Total_Harga, Deadline FROM pesanan WHERE Status_Pesanan IN ('Selesai', 'Siap Diambil') AND MONTH(Deadline) = '$m' AND YEAR(Deadline) = '$y'");
    
    while ($r = mysqli_fetch_assoc($q_bulanan)) {
        $tgl = (int)date('d', strtotime($r['Deadline']));
        $w = ceil($tgl / 7) - 1; // Index array 0 sampai 3
        if ($w > 3) $w = 3; // Gabungkan tanggal 29-31 ke minggu ke-4
        $data_bulanan[$w] += (int)$r['Total_Harga'];
    }
}

include '../layouts/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-primary">Dashboard Utama</h4>
        <p class="text-muted mb-0 small">Ringkasan aktivitas operasional Cahaya Trophy hari ini.</p>
    </div>
    
    <?php if($_SESSION['role'] != 'Operator'): ?>
    <a href="../orders/tambah.php" class="btn btn-primary fw-medium shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Pesanan Baru
    </a>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex"><i class="bi bi-receipt fs-5"></i></div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Semua</span>
                </div>
                <div class="text-muted small fw-medium mb-1">Total Pesanan</div>
                <h3 class="fw-bold mb-0 text-dark"><?= number_format($total_pesanan, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2 d-flex"><i class="bi bi-gear fs-5"></i></div>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Aktif</span>
                </div>
                <div class="text-muted small fw-medium mb-1">Pesanan Diproses</div>
                <h3 class="fw-bold mb-0 text-dark"><?= number_format($pesanan_diproses, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 d-flex"><i class="bi bi-check2-circle fs-5"></i></div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Lunas</span>
                </div>
                <div class="text-muted small fw-medium mb-1">Pesanan Selesai</div>
                <h3 class="fw-bold mb-0 text-dark"><?= number_format($pesanan_selesai, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator'): ?>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 d-flex"><i class="bi bi-wallet2 fs-5"></i></div>
                    <span class="text-muted small fw-medium align-self-center">Bulan Ini</span>
                </div>
                <div class="text-muted small fw-medium mb-1">Total Omzet</div>
                <h3 class="fw-bold mb-0 text-dark"><?= formatRupiahSingkat($total_omzet) ?></h3>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator'): ?>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Tren Omzet</h6>
                        <p class="text-muted small mb-0" id="chartSubtitle">Visualisasi pendapatan 7 hari terakhir.</p>
                    </div>
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button class="btn btn-outline-primary active fw-medium" id="btnMingguan" onclick="ubahGrafik('mingguan')">Mingguan</button>
                        <button class="btn btn-outline-primary fw-medium" id="btnBulanan" onclick="ubahGrafik('bulanan')">Bulanan</button>
                    </div>
                </div>
                <div style="height: 250px;">
                    <canvas id="omzetChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="<?= (isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator') ? 'col-lg-4' : 'col-lg-6' ?>">
        <div class="card border-danger border-opacity-25 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold text-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan Stok Kritis</h6>
                <div class="d-flex flex-column gap-3">
                    <?php 
                    if (mysqli_num_rows($q_stok) > 0) {
                        while($row = mysqli_fetch_assoc($q_stok)) { 
                    ?>
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background-color: #fff5f5; border: 1px solid #ffe3e3;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark lh-1 mb-1" style="font-size: 0.85rem;"><?= $row['Nama_Bahan'] ?></div>
                                    <div class="text-danger fw-medium" style="font-size: 0.75rem;">Sisa: <?= $row['Jumlah_Stok'] ?> <?= $row['Satuan'] ?></div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-danger px-3 rounded-pill fw-medium" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalRestock">Restock</button>
                        </div>
                    <?php 
                        } 
                    } else {
                        echo '<div class="text-center text-muted mt-5 pt-3"><i class="bi bi-check-circle text-success fs-1 mb-2 d-block"></i><p class="small">Semua stok bahan baku aman terkendali.</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">Pesanan Mendekati Deadline</h6>
                <a href="../orders/index.php?sort=deadline" class="text-decoration-none small fw-medium">Lihat Semua</a>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">NO PESANAN</th>
                                <th>PELANGGAN</th>
                                <th>TANGGAL DEADLINE</th>
                                <th>STATUS</th>
                                <th class="pe-4 text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.9rem;">
                            <?php 
                            if (mysqli_num_rows($q_deadline) > 0) {
                                while($row = mysqli_fetch_assoc($q_deadline)) { 
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#<?= $row['NO_PESANAN'] ?></td>
                                <td class="fw-bold text-dark"><?= $row['Nama_Pelanggan'] ?></td>
                                <td>
                                    <span class="text-danger fw-bold"><i class="bi bi-clock-history me-1"></i> <?= formatTanggal($row['Deadline']) ?></span>
                                </td>
                                <td><?= badgeStatusPesanan($row['Status_Pesanan']) ?></td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="../orders/detail.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-sm btn-light border text-primary shadow-sm" data-bs-toggle="tooltip" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../orders/edit.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-sm btn-light border text-secondary shadow-sm" data-bs-toggle="tooltip" title="Update Status">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator'): ?>
                                        <a href="../orders/hapus.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-sm btn-light border text-danger shadow-sm btn-hapus" data-pesan="Pesanan <?= $row['NO_PESANAN'] ?>" data-bs-toggle="tooltip" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted py-5">Yeay! Tidak ada pesanan yang mendesak.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRestock" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Pintasan Restock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted">Untuk melakukan penambahan stok masuk, silakan akses menu <strong>Inventory Management</strong>.</p>
                <a href="../inventory/index.php" class="btn btn-primary fw-bold px-4 py-2 mt-2 w-100">Menuju Halaman Inventory</a>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Super Administrator'): ?>
<script>
    // Menyuntikkan array PHP ke JavaScript menggunakan json_encode()
    const dataMingguan = {
        labels: <?= json_encode($label_mingguan) ?>,
        data: <?= json_encode($data_mingguan) ?>
    };

    const dataBulanan = {
        labels: <?= json_encode($label_bulanan) ?>,
        data: <?= json_encode($data_bulanan) ?>
    };

    const ctx = document.getElementById('omzetChart').getContext('2d');
    let omzetChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dataMingguan.labels,
            datasets: [{
                label: 'Omzet (Rp)',
                data: dataMingguan.data,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0d6efd',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    function ubahGrafik(periode) {
        const btnMingguan = document.getElementById('btnMingguan');
        const btnBulanan = document.getElementById('btnBulanan');
        const subtitle = document.getElementById('chartSubtitle');

        if (periode === 'mingguan') {
            btnMingguan.classList.add('active');
            btnBulanan.classList.remove('active');
            subtitle.innerText = "Visualisasi pendapatan 7 hari terakhir.";
            
            omzetChart.data.labels = dataMingguan.labels;
            omzetChart.data.datasets[0].data = dataMingguan.data;
        } else {
            btnBulanan.classList.add('active');
            btnMingguan.classList.remove('active');
            subtitle.innerText = "Visualisasi pendapatan selama 4 minggu bulan ini.";
            
            omzetChart.data.labels = dataBulanan.labels;
            omzetChart.data.datasets[0].data = dataBulanan.data;
        }
        omzetChart.update();
    }
</script>
<?php endif; ?>