<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$id_pesanan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// 1. PROSES UPDATE DATA JIKA TOMBOL SIMPAN DITEKAN
if(isset($_POST['simpan_edit'])) {
    $status_pesanan = $_POST['status_pesanan'];
    
    // PEMISAHAN LOGIKA BACKEND BERDASARKAN ROLE
    if($_SESSION['role'] == 'Operator') {
        // Operator HANYA boleh mengubah status_pesanan
        $update_query = "UPDATE pesanan SET Status_Pesanan = '$status_pesanan' WHERE NO_PESANAN = '$id_pesanan'";
    } else {
        // Admin & Kasir memproses semuanya (Deadline & Uang)
        $deadline = $_POST['deadline'];
        $nominal_dp_baru = $_POST['nominal_dp'];
        $total_harga = $_POST['total_harga'];
        
        $sisa_tagihan = $total_harga - $nominal_dp_baru;
        if($sisa_tagihan < 0) $sisa_tagihan = 0;

        $update_query = "UPDATE pesanan SET 
                            Status_Pesanan = '$status_pesanan', 
                            Deadline = '$deadline', 
                            Nominal_DP = '$nominal_dp_baru', 
                            Sisa_Tagihan = '$sisa_tagihan' 
                         WHERE NO_PESANAN = '$id_pesanan'";
    }

    if(mysqli_query($koneksi, $update_query)) {
        header("Location: index.php?pesan=berhasil");
        exit();
    }
}

$page_title = "Update Pesanan - Cahaya Trophy";

// 2. AMBIL DATA PESANAN UNTUK DITAMPILKAN DI FORM
$q_data = mysqli_query($koneksi, "
    SELECT p.*, pel.Nama_Pelanggan, dp.Kategori_Produk 
    FROM pesanan p
    JOIN pelanggan pel ON p.ID_PELANGGAN = pel.ID_PELANGGAN
    LEFT JOIN detail_pesanan dp ON p.NO_PESANAN = dp.NO_PESANAN
    WHERE p.NO_PESANAN = '$id_pesanan'
");

if(mysqli_num_rows($q_data) == 0) {
    header("Location: index.php");
    exit();
}

$row = mysqli_fetch_assoc($q_data);
include '../layouts/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="btn btn-sm btn-light border mb-3 text-muted">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <h4 class="fw-bold mb-1 text-primary">Update Pesanan #<?= $row['NO_PESANAN'] ?></h4>
    <p class="text-muted small mb-0">Pelanggan: <span class="fw-bold text-dark"><?= $row['Nama_Pelanggan'] ?></span> | Produk: <?= $row['Kategori_Produk'] ?></p>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                
                <form action="" method="POST">
                    <input type="hidden" name="total_harga" id="total_harga" value="<?= $row['Total_Harga'] ?>">

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Update Status Pesanan</label>
                        <select name="status_pesanan" class="form-select bg-light">
                            <option value="Menunggu DP" <?= $row['Status_Pesanan'] == 'Menunggu DP' ? 'selected' : '' ?>>Menunggu DP</option>
                            <option value="Diproses" <?= $row['Status_Pesanan'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="Produksi" <?= $row['Status_Pesanan'] == 'Produksi' ? 'selected' : '' ?>>Masuk Produksi</option>
                            <option value="Siap Diambil" <?= $row['Status_Pesanan'] == 'Siap Diambil' ? 'selected' : '' ?>>Siap Diambil / Kirim</option>
                            <option value="Selesai" <?= $row['Status_Pesanan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Update Tanggal Deadline</label>
                        <?php if($_SESSION['role'] == 'Operator'): ?>
                            <input type="date" class="form-control bg-light" value="<?= $row['Deadline'] ?>" disabled>
                        <?php else: ?>
                            <input type="date" name="deadline" class="form-control bg-light" value="<?= $row['Deadline'] ?>" required>
                        <?php endif; ?>
                    </div>

                    <hr class="border-secondary opacity-25 mb-4">

                    <?php if($_SESSION['role'] != 'Operator'): ?>
                        <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 text-primary me-2"></i>Update Pembayaran</h6>
                        
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Total Tagihan Awal:</span>
                            <span class="fw-bold text-dark fs-6"><?= formatRupiah($row['Total_Harga']) ?></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Uang Masuk / DP yang sudah dibayar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" name="nominal_dp" id="nominal_dp" class="form-control" value="<?= $row['Nominal_DP'] ?>" min="0">
                                <button type="button" class="btn btn-success" id="btnLunas">Lunas</button>
                            </div>
                            <div class="form-text small">Ubah nominal di atas jika pelanggan melakukan penambahan DP atau pelunasan.</div>
                        </div>

                        <div class="p-3 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-danger small fw-bold">Sisa Tagihan:</span>
                                <span class="fw-bold text-danger fs-5" id="txtSisa"><?= formatRupiah($row['Sisa_Tagihan']) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info small border-0 shadow-sm rounded-3 mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i> Sebagai Operator Produksi, Anda hanya memiliki akses untuk memperbarui <b>Status Pesanan</b>. Detail pembayaran sepenuhnya dikelola oleh Kasir.
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="simpan_edit" class="btn btn-primary fw-bold w-100 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi JavaScript ini hanya akan berjalan jika elemen form-nya ada (Bukan Operator)
    const inputDP = document.getElementById('nominal_dp');
    if(inputDP) {
        const totalHarga = parseInt(document.getElementById('total_harga').value);
        const txtSisa = document.getElementById('txtSisa');
        const btnLunas = document.getElementById('btnLunas');

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function hitungSisa() {
            let dp = parseInt(inputDP.value) || 0;
            let sisa = totalHarga - dp;
            if(sisa < 0) sisa = 0;
            txtSisa.innerText = formatRupiah(sisa);
        }

        inputDP.addEventListener('input', hitungSisa);

        btnLunas.addEventListener('click', function() {
            inputDP.value = totalHarga;
            hitungSisa();
        });
    }
});
</script>