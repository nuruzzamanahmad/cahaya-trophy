<?php
session_start();
require_once '../helper/koneksi.php';
require_once '../helper/fungsi.php';

// Proteksi Keamanan: Hanya Super Admin yang boleh lihat laporan keuangan
if (!isset($_SESSION['id_admin']) || $_SESSION['role'] != 'Super Administrator') {
    header("Location: ../dashboard/index.php");
    exit();
}

$page_title = "Laporan Finansial - Cahaya Trophy";

// Filter Waktu (Semua, Bulan Ini, Tahun Ini)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where_clause = "";
$and_clause = "";

if ($filter == 'bulan_ini') {
    $where_clause = "WHERE MONTH(Deadline) = MONTH(CURRENT_DATE()) AND YEAR(Deadline) = YEAR(CURRENT_DATE())";
    $and_clause = "AND MONTH(Deadline) = MONTH(CURRENT_DATE()) AND YEAR(Deadline) = YEAR(CURRENT_DATE())";
} elseif ($filter == 'tahun_ini') {
    $where_clause = "WHERE YEAR(Deadline) = YEAR(CURRENT_DATE())";
    $and_clause = "AND YEAR(Deadline) = YEAR(CURRENT_DATE())";
}

// 1. Hitung Total Nilai Pesanan (Omzet Kotor - Hanya Pesanan Selesai)
$q_omzet = mysqli_query($koneksi, "SELECT SUM(Total_Harga) as omzet FROM pesanan WHERE Status_Pesanan IN ('Selesai', 'Siap Diambil') $and_clause");
$omzet = mysqli_fetch_assoc($q_omzet)['omzet'] ?? 0;

// 2. Hitung Sisa Piutang (Belum Lunas)
$q_piutang = mysqli_query($koneksi, "SELECT SUM(Sisa_Tagihan) as piutang, COUNT(*) as jml_order FROM pesanan WHERE Sisa_Tagihan > 0 $and_clause");
$d_piutang = mysqli_fetch_assoc($q_piutang);
$piutang = $d_piutang['piutang'] ?? 0;
$jml_piutang = $d_piutang['jml_order'] ?? 0;

// 3. Hitung Total Uang Masuk (Kas Bersih)
$q_kas = mysqli_query($koneksi, "SELECT SUM(Total_Harga - Sisa_Tagihan) as kas FROM pesanan $where_clause");
$kas_masuk = mysqli_fetch_assoc($q_kas)['kas'] ?? 0;

// =======================================
// LOGIKA GRAFIK DINAMIS
// =======================================
$labels = [];
$data_penjualan = [];
$data_piutang = [];
$judul_grafik = "";

if ($filter == 'bulan_ini') {
    // Filter Bulan Ini: Tampilkan per Minggu (Minggu 1 s/d 5)
    $judul_grafik = "Grafik Tren Keuangan (Bulan Ini)";
    $labels = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5'];
    $data_penjualan = [0, 0, 0, 0, 0];
    $data_piutang = [0, 0, 0, 0, 0];

    $q_grafik = mysqli_query($koneksi, "
        SELECT CEIL(DAY(Deadline)/7) as minggu, 
               SUM(CASE WHEN Status_Pesanan IN ('Selesai', 'Siap Diambil') THEN Total_Harga ELSE 0 END) as omzet,
               SUM(CASE WHEN Sisa_Tagihan > 0 THEN Sisa_Tagihan ELSE 0 END) as piutang
        FROM pesanan 
        WHERE MONTH(Deadline) = MONTH(CURRENT_DATE()) AND YEAR(Deadline) = YEAR(CURRENT_DATE())
        GROUP BY CEIL(DAY(Deadline)/7)
    ");
    while($row = mysqli_fetch_assoc($q_grafik)) {
        $idx = (int)$row['minggu'] - 1;
        if($idx >= 0 && $idx < 5) {
            $data_penjualan[$idx] = (int)$row['omzet'];
            $data_piutang[$idx] = (int)$row['piutang'];
        }
    }
} elseif ($filter == 'tahun_ini') {
    // Filter Tahun Ini: Tampilkan per Bulan (Januari s/d Desember)
    $judul_grafik = "Grafik Tren Keuangan (Tahun " . date('Y') . ")";
    $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    $data_penjualan = array_fill(0, 12, 0);
    $data_piutang = array_fill(0, 12, 0);

    $q_grafik = mysqli_query($koneksi, "
        SELECT MONTH(Deadline) as bulan, 
               SUM(CASE WHEN Status_Pesanan IN ('Selesai', 'Siap Diambil') THEN Total_Harga ELSE 0 END) as omzet,
               SUM(CASE WHEN Sisa_Tagihan > 0 THEN Sisa_Tagihan ELSE 0 END) as piutang
        FROM pesanan 
        WHERE YEAR(Deadline) = YEAR(CURRENT_DATE())
        GROUP BY MONTH(Deadline)
    ");
    while($row = mysqli_fetch_assoc($q_grafik)) {
        $idx = (int)$row['bulan'] - 1;
        if($idx >= 0 && $idx < 12) {
            $data_penjualan[$idx] = (int)$row['omzet'];
            $data_piutang[$idx] = (int)$row['piutang'];
        }
    }
} else {
    // Filter Semua: Tampilkan per Tahun (Berdasarkan Tahun yang ada di Database)
    $judul_grafik = "Grafik Tren Keuangan (Semua Waktu)";
    $q_years = mysqli_query($koneksi, "SELECT MIN(YEAR(Deadline)) as min_y, MAX(YEAR(Deadline)) as max_y FROM pesanan");
    $d_years = mysqli_fetch_assoc($q_years);
    $min_y = $d_years['min_y'] ? (int)$d_years['min_y'] : (int)date('Y');
    $max_y = $d_years['max_y'] ? (int)$d_years['max_y'] : (int)date('Y');
    
    for ($y = $min_y; $y <= $max_y; $y++) {
        $labels[] = (string)$y;
        $data_penjualan[] = 0;
        $data_piutang[] = 0;
    }

    $q_grafik = mysqli_query($koneksi, "
        SELECT YEAR(Deadline) as tahun, 
               SUM(CASE WHEN Status_Pesanan IN ('Selesai', 'Siap Diambil') THEN Total_Harga ELSE 0 END) as omzet,
               SUM(CASE WHEN Sisa_Tagihan > 0 THEN Sisa_Tagihan ELSE 0 END) as piutang
        FROM pesanan 
        GROUP BY YEAR(Deadline)
    ");
    while($row = mysqli_fetch_assoc($q_grafik)) {
        $idx = (int)$row['tahun'] - $min_y;
        if($idx >= 0 && $idx < count($labels)) {
            $data_penjualan[$idx] = (int)$row['omzet'];
            $data_piutang[$idx] = (int)$row['piutang'];
        }
    }
}

// Convert ke JSON untuk Chart.js
$json_labels = json_encode($labels);
$json_penjualan = json_encode($data_penjualan);
$json_piutang = json_encode($data_piutang);

include '../layouts/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-primary">Ringkasan Performa</h4>
        <p class="text-muted mb-0 small">Pantau kesehatan finansial Cahaya Trophy secara real-time.</p>
    </div>
    
    <div class="btn-group shadow-sm">
        <a href="?filter=semua" class="btn btn-<?= $filter == 'semua' ? 'primary' : 'light bg-white border' ?> fw-medium">Semua</a>
        <a href="?filter=bulan_ini" class="btn btn-<?= $filter == 'bulan_ini' ? 'primary' : 'light bg-white border' ?> fw-medium">Bulan Ini</a>
        <a href="?filter=tahun_ini" class="btn btn-<?= $filter == 'tahun_ini' ? 'primary' : 'light bg-white border' ?> fw-medium">Tahun Ini</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-4">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 60px; height: 60px;">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium mb-1">Total Nilai Pesanan (Omzet)</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= formatRupiah($omzet) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-4">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 60px; height: 60px;">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium mb-1">Total Uang Masuk (Kas)</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= formatRupiah($kas_masuk) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 border-end border-danger border-4">
            <div class="card-body p-4 d-flex align-items-center gap-4">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-receipt-cutoff fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium mb-1">Sisa Piutang (Belum Lunas)</div>
                    <h3 class="fw-bold mb-0 text-danger"><?= formatRupiah($piutang) ?></h3>
                    <div class="text-danger small mt-1"><?= $jml_piutang ?> Order belum lunas</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><?= $judul_grafik ?></h6>
        
        <div class="d-flex gap-3 small fw-medium">
            <div class="d-flex align-items-center gap-1"><span style="width: 10px; height: 10px; border-radius: 50%; background-color: #0d6efd; display: inline-block;"></span> Penjualan Selesai</div>
            <div class="d-flex align-items-center gap-1"><span style="width: 10px; height: 10px; border-radius: 50%; background-color: #ffc107; display: inline-block;"></span> Piutang Berjalan</div>
        </div>
    </div>
    <div class="card-body p-4">
        <div style="height: 350px;">
            <canvas id="keuanganChart"></canvas>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('keuanganChart').getContext('2d');
        
        const dataKeuangan = {
            labels: <?= $json_labels ?>,
            datasets: [
                {
                    label: 'Omzet Selesai',
                    data: <?= $json_penjualan ?>,
                    backgroundColor: '#0d6efd',
                    borderRadius: 4,
                    barPercentage: 0.5
                },
                {
                    label: 'Piutang',
                    data: <?= $json_piutang ?>,
                    backgroundColor: '#ffc107',
                    borderRadius: 4,
                    barPercentage: 0.5
                }
            ]
        };

        new Chart(ctx, {
            type: 'bar',
            data: dataKeuangan,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5, 5], color: '#f0f0f0' },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value/1000000) + ' Jt';
                                return value;
                            }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>

<?php include '../layouts/footer.php'; ?>