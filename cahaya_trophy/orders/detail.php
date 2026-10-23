<?php
session_start();
require '../helper/koneksi.php';
require '../helper/fungsi.php';

// Proteksi Keamanan: Wajib login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Detail Pesanan - Cahaya Trophy";

// Ambil ID Pesanan dari URL
$id_pesanan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// 1. Ambil Data Utama Pesanan & Pelanggan
$q_utama = mysqli_query($koneksi, "
    SELECT p.*, pel.* FROM pesanan p
    JOIN pelanggan pel ON p.ID_PELANGGAN = pel.ID_PELANGGAN
    WHERE p.NO_PESANAN = '$id_pesanan'
");

// Jika pesanan tidak ditemukan
if(mysqli_num_rows($q_utama) == 0) {
    header("Location: index.php");
    exit();
}
$row = mysqli_fetch_assoc($q_utama);

// 2. Ambil Daftar Item Produk (Keranjang) untuk Nota Ini
$q_items = mysqli_query($koneksi, "SELECT * FROM detail_pesanan WHERE NO_PESANAN = '$id_pesanan'");

include '../layouts/header.php';
?>

<style>
    @media print {
        @page {
            /* Setelan margin kertas ben muat akeh */
            margin: 1cm;
        }
        
        /* Matikan kabeh UI Website ben ra melu ke-print */
        body * {
            visibility: hidden;
        }
        
        /* Mung tampilake area nota */
        #areaCetakNota, #areaCetakNota * {
            visibility: visible;
        }
        
        #areaCetakNota {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            font-family: Arial, sans-serif !important;
            color: #000 !important;
        }

        /* Ilangi warna background web ben irit tinta */
        body { background-color: #fff !important; }
    }
</style>

<div class="mb-4 d-print-none">
    <a href="index.php" class="btn btn-sm btn-light border mb-3 text-muted">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Pesanan
    </a>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1 text-primary">Detail Pesanan #<?= $row['NO_PESANAN'] ?></h4>
            <p class="text-muted small mb-0">Informasi lengkap transaksi dan item keranjang produk.</p>
        </div>
        <div>
            <?= badgeStatusPesanan($row['Status_Pesanan']) ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 d-print-none">
    <div class="col-lg-8">
        
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
                <h6 class="fw-bold text-dark"><i class="bi bi-person-badge text-primary me-2"></i>Informasi Pelanggan</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-6 col-md-6">
                        <div class="text-muted small fw-medium mb-1">Nama Lengkap</div>
                        <div class="fw-bold text-dark"><?= $row['Nama_Pelanggan'] ?> <span class="text-muted small fw-normal">(<?= $row['ID_PELANGGAN'] ?>)</span></div>
                    </div>
                    <div class="col-6 col-md-6">
                        <div class="text-muted small fw-medium mb-1">Nomor WhatsApp</div>
                        <div><a href="https://wa.me/<?= str_replace('08', '628', $row['No_Whatsapp']) ?>" target="_blank" class="text-decoration-none text-success fw-bold"><i class="bi bi-whatsapp me-1"></i> <?= $row['No_Whatsapp'] ?></a></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small fw-medium mb-1">Alamat Lengkap</div>
                        <div class="text-dark"><?= $row['Alamat'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
                <h6 class="fw-bold text-dark"><i class="bi bi-bag-check text-primary me-2"></i>Daftar Item Pesanan</h6>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="min-width: 500px;">
                        <thead class="table-light text-muted" style="font-size: 0.8rem;">
                            <tr>
                                <th class="ps-4">PRODUK</th>
                                <th class="text-center">QTY</th>
                                <th class="pe-4">TEKS / CATATAN CUSTOM</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.85rem;">
                            <?php 
                            mysqli_data_seek($q_items, 0); 
                            if(mysqli_num_rows($q_items) > 0) {
                                while($item = mysqli_fetch_assoc($q_items)) {
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark w-25"><?= $item['Kategori_Produk'] ?></td>
                                <td class="text-center w-auto">
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 border border-primary border-opacity-25">
                                        <?= $item['Qty'] ?> Unit
                                    </span>
                                </td>
                                <td class="pe-4 text-muted fst-italic w-50"><?= !empty($item['Teks_Kustom']) ? nl2br($item['Teks_Kustom']) : '-' ?></td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="3" class="text-center text-muted py-4">Tidak ada detail produk.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-primary text-white border-bottom-0 pt-4 pb-3 px-4 rounded-top-4">
                <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Ringkasan Transaksi</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Tenggat Waktu:</span>
                    <span class="fw-bold text-danger"><?= formatTanggal($row['Deadline']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                    <span class="text-muted small">Metode Ambil:</span>
                    <span class="fw-bold text-dark"><?= $row['Metode_Pengambilan'] ?></span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Tagihan:</span>
                    <span class="fw-bold text-dark fs-5"><?= formatRupiah($row['Total_Harga']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3 text-success">
                    <span>Uang Masuk (DP):</span>
                    <span class="fw-bold">- <?= formatRupiah($row['Nominal_DP']) ?></span>
                </div>
                
                <div class="p-3 <?= ($row['Sisa_Tagihan'] > 0) ? 'bg-danger border-danger text-danger' : 'bg-success border-success text-success' ?> bg-opacity-10 rounded text-center border border-opacity-25 mt-4">
                    <div class="small fw-bold mb-1 text-uppercase">SISA KEKURANGAN</div>
                    <h3 class="fw-bold mb-0"><?= formatRupiah($row['Sisa_Tagihan']) ?></h3>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <?php if($_SESSION['role'] == 'Operator'): ?>
                        <a href="edit.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-primary fw-medium">
                            <i class="bi bi-gear-fill me-1"></i> Update Status Produksi
                        </a>
                    <?php else: ?>
                        <a href="edit.php?id=<?= $row['NO_PESANAN'] ?>" class="btn btn-primary fw-medium">
                            <i class="bi bi-pencil-square me-1"></i> Update Status / Pembayaran
                        </a>
                        <button type="button" onclick="window.print()" class="btn btn-outline-secondary fw-medium">
                            <i class="bi bi-printer me-1"></i> Cetak Nota
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="areaCetakNota" class="d-none d-print-block">
    <div style="text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 20px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Cahaya Trophy</h2>
        <p style="margin: 5px 0 0 0; font-size: 13px;">Pusat Pembuatan Piala, Medali, dan Plakat Semarang</p>
        <p style="margin: 2px 0 0 0; font-size: 13px;">WhatsApp: 08123456789</p>
    </div>

    <table style="width: 100%; margin-bottom: 20px; font-size: 13px; line-height: 1.5;">
        <tr>
            <td style="width: 15%; font-weight: bold;">No. Nota</td>
            <td style="width: 35%;">: <?= $row['NO_PESANAN'] ?></td>
            <td style="width: 15%; font-weight: bold;">Pemesan</td>
            <td style="width: 35%;">: <?= $row['Nama_Pelanggan'] ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tgl. Pesan</td>
            <td>: <?= date('d/m/Y') ?></td>
            <td style="font-weight: bold;">No. WA</td>
            <td>: <?= $row['No_Whatsapp'] ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Deadline</td>
            <td>: <?= date('d/m/Y', strtotime($row['Deadline'])) ?></td>
            <td style="font-weight: bold;">Alamat</td>
            <td>: <?= $row['Alamat'] ?></td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="border: 1px solid #000; padding: 8px; width: 5%; text-align: center;">No</th>
                <th style="border: 1px solid #000; padding: 8px; width: 35%;">Nama Produk</th>
                <th style="border: 1px solid #000; padding: 8px; width: 10%; text-align: center;">Qty</th>
                <th style="border: 1px solid #000; padding: 8px; width: 50%;">Teks / Catatan Piala</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            mysqli_data_seek($q_items, 0); 
            $no_urut = 1;
            while($item_print = mysqli_fetch_assoc($q_items)) {
            ?>
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;"><?= $no_urut++ ?></td>
                <td style="border: 1px solid #000; padding: 8px; vertical-align: top;"><b><?= $item_print['Kategori_Produk'] ?></b></td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;"><?= $item_print['Qty'] ?></td>
                <td style="border: 1px solid #000; padding: 8px; vertical-align: top; font-style: italic;">
                    <?= !empty($item_print['Teks_Kustom']) ? nl2br($item_print['Teks_Kustom']) : '-' ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <table style="width: 100%; font-size: 13px; margin-top: 10px;">
        <tr>
            <td style="width: 30%; text-align: center; vertical-align: bottom;">
                <p style="margin-bottom: 70px;">Tanda Terima,</p>
                <p style="font-weight: bold; text-decoration: underline; margin: 0;"><?= $row['Nama_Pelanggan'] ?></p>
            </td>
            
            <td style="width: 35%; text-align: center; vertical-align: bottom;">
                <p style="margin-bottom: 70px; font-weight: bold;">Hormat Kami,</p>
                <p style="font-weight: bold; margin: 0;">( Cahaya Trophy )</p>
                <p style="font-size: 10px; color: #555; margin: 2px 0 0 0;">*Area Stempel</p>
            </td>
            
            <td style="width: 35%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0;">Total Tagihan</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold;">Rp <?= number_format($row['Total_Harga'],0,',','.') ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0;">Uang Muka (DP)</td>
                        <td style="padding: 6px 0; text-align: right;">Rp <?= number_format($row['Nominal_DP'],0,',','.') ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border-top: 2px solid #000; padding-top: 4px;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-weight: bold; font-size: 15px;">Sisa Kekurangan</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold; font-size: 15px;">Rp <?= number_format($row['Sisa_Tagihan'],0,',','.') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <div style="text-align: center; margin-top: 30px; font-size: 11px; color: #555; font-style: italic;">
        Terima kasih telah mempercayakan pembuatan produk Anda di Cahaya Trophy.
    </div>
</div>

<?php include '../layouts/footer.php'; ?>