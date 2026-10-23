<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$id_pelanggan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

$q_pelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE ID_PELANGGAN = '$id_pelanggan'");
if(mysqli_num_rows($q_pelanggan) == 0) {
    header("Location: index.php");
    exit();
}
$pelanggan = mysqli_fetch_assoc($q_pelanggan);

$q_orders = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE ID_PELANGGAN = '$id_pelanggan' ORDER BY NO_PESANAN DESC");

$total_dibelanjakan = 0;
$total_order = mysqli_num_rows($q_orders);

$page_title = "Profil Pelanggan - Cahaya Trophy";
include '../layouts/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="btn btn-sm btn-light border mb-3 text-muted">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Pelanggan
    </a>
    <h4 class="fw-bold mb-1 text-primary">Profil & Riwayat Pelanggan</h4>
    <p class="text-muted small mb-0">Informasi detail pelanggan dan rekam jejak pesanan mereka.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($pelanggan['Nama_Pelanggan'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= $pelanggan['Nama_Pelanggan'] ?></h5>
                <p class="text-muted small mb-3">ID: <?= $pelanggan['ID_PELANGGAN'] ?></p>
                
                <hr class="border-secondary opacity-25">
                
                <div class="text-start mt-4">
                    <div class="mb-3">
                        <div class="text-muted small fw-bold mb-1">Nomor WhatsApp</div>
                        <a href="https://wa.me/<?= str_replace('08', '628', $pelanggan['No_Whatsapp']) ?>" target="_blank" class="text-decoration-none text-success fw-bold fs-6">
                            <i class="bi bi-whatsapp me-2"></i><?= $pelanggan['No_Whatsapp'] ?>
                        </a>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold mb-1">Alamat Domisili</div>
                        <div class="text-dark small lh-base"><?= $pelanggan['Alamat'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold text-dark"><i class="bi bi-bag-check text-primary me-2"></i>Riwayat Pesanan (<?= $total_order ?>)</h6>
            </div>
            <div class="card-body p-4 pt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="py-3">NO. ORDER</th>
                                <th>TANGGAL DEADLINE</th>
                                <th>TOTAL BIAYA</th>
                                <th>STATUS</th>
                                <th>NOTA</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.85rem;">
                            <?php 
                            if($total_order > 0) {
                                while($order = mysqli_fetch_assoc($q_orders)) {
                                    $total_dibelanjakan += $order['Total_Harga'];
                            ?>
                            <tr>
                                <td class="fw-bold text-primary">#<?= $order['NO_PESANAN'] ?></td>
                                <td class="text-muted"><?= formatTanggal($order['Deadline']) ?></td>
                                <td class="fw-bold text-dark"><?= formatRupiah($order['Total_Harga']) ?></td>
                                <td><?= badgeStatusPesanan($order['Status_Pesanan']) ?></td>
                                <td>
                                    <a href="../orders/detail.php?id=<?= $order['NO_PESANAN'] ?>" class="btn btn-sm btn-light border text-primary px-3">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-5 text-muted">Pelanggan ini belum pernah melakukan pesanan.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if($total_order > 0): ?>
            <div class="card-footer bg-light border-top-0 p-3 text-end rounded-bottom-4">
                <span class="text-muted small me-3">Estimasi Total Pembelanjaan:</span>
                <span class="fs-5 fw-bold text-dark"><?= formatRupiah($total_dibelanjakan) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>