<?php
session_start();
require '../helper/koneksi.php';

// PERUBAHAN 1: Membuka gembok agar Kasir juga bisa mengakses halaman ini
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Inventory Management - Cahaya Trophy";

// Ambil Data Ringkasan Atas
$q_total_sku = mysqli_query($koneksi, "SELECT COUNT(ID_BAHAN) as total FROM bahanbaku");
$total_sku = mysqli_fetch_assoc($q_total_sku)['total'];

$q_kritis = mysqli_query($koneksi, "SELECT COUNT(ID_BAHAN) as kritis FROM bahanbaku WHERE Jumlah_Stok <= Batas_Kritis");
$stok_kritis = mysqli_fetch_assoc($q_kritis)['kritis'];

$q_masuk_hari_ini = mysqli_query($koneksi, "SELECT SUM(Jumlah) as masuk FROM riwayat_mutasi WHERE Jenis = 'Masuk' AND DATE(Tanggal) = CURDATE()");
$masuk_hari_ini = mysqli_fetch_assoc($q_masuk_hari_ini)['masuk'] ?? 0;

// LOGIKA PENCARIAN
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where_stok = "";
if (!empty($search)) {
    $where_stok = " WHERE Nama_Bahan LIKE '%$search%' OR ID_BAHAN LIKE '%$search%' OR Kategori LIKE '%$search%' ";
}

// Ambil Data Stok (dengan filter pencarian jika ada) & Mutasi
$q_stok = mysqli_query($koneksi, "SELECT * FROM bahanbaku $where_stok ORDER BY Kategori ASC, Nama_Bahan ASC");
$q_mutasi = mysqli_query($koneksi, "SELECT r.*, b.Nama_Bahan, b.Satuan FROM riwayat_mutasi r JOIN bahanbaku b ON r.ID_BAHAN = b.ID_BAHAN ORDER BY r.Tanggal DESC LIMIT 50");

include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-primary">Inventory Management</h4>
        <p class="text-muted small mb-0">Kelola ketersediaan bahan baku, barang masuk, dan barang keluar (rusak/hilang).</p>
    </div>
    
    <?php if($_SESSION['role'] != 'Kasir'): ?>
    <div class="d-flex gap-2">
        <button class="btn btn-primary fw-medium" data-bs-toggle="modal" data-bs-target="#modalMasuk">
            <i class="bi bi-box-arrow-in-down me-1"></i> Barang Masuk
        </button>
        <button class="btn btn-outline-danger fw-medium" data-bs-toggle="modal" data-bs-target="#modalKeluar">
            <i class="bi bi-box-arrow-up me-1"></i> Barang Keluar
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if(isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Aksi inventory berhasil diproses!
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(!empty($search)): ?>
    <div class="alert alert-info py-2 rounded-3 small mb-4 d-flex align-items-center justify-content-between">
        <div><i class="bi bi-search me-2"></i> Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong></div>
        <a href="index.php" class="btn btn-sm btn-danger fw-bold text-decoration-none py-0 px-2"><i class="bi bi-x-lg"></i> Reset Filter</a>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-bold mb-1 text-uppercase">Total Item (SKU)</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $total_sku ?> <span class="fs-6 text-muted fw-normal">Jenis</span></h3>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 <?= $stok_kritis > 0 ? 'border-danger border-2' : '' ?>">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-bold mb-1 text-uppercase">Butuh Restock (Kritis)</div>
                    <h3 class="fw-bold mb-0 <?= $stok_kritis > 0 ? 'text-danger' : 'text-dark' ?>"><?= $stok_kritis ?> <span class="fs-6 text-muted fw-normal">Item</span></h3>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-bold mb-1 text-uppercase">Masuk Hari Ini</div>
                    <h3 class="fw-bold mb-0 text-success">+<?= $masuk_hari_ini ?> <span class="fs-6 text-muted fw-normal">Pcs</span></h3>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-truck fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
        <ul class="nav nav-pills" id="inventoryTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-medium" data-bs-toggle="pill" data-bs-target="#tab-stok" type="button"><i class="bi bi-list-ul me-1"></i> Daftar Stok</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-medium" data-bs-toggle="pill" data-bs-target="#tab-mutasi" type="button"><i class="bi bi-clock-history me-1"></i> Riwayat Mutasi</button>
            </li>
        </ul>
        
        <?php if($_SESSION['role'] != 'Kasir'): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBaru">
            <i class="bi bi-plus"></i> Tambah Item Baru
        </button>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <div class="tab-content" id="inventoryTabContent">
            
            <div class="tab-pane fade show active" id="tab-stok" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">NAMA BAHAN / PRODUK</th>
                                <th>KATEGORI</th>
                                <th class="text-center">SISA STOK</th>
                                <th class="text-center">BATAS KRITIS</th>
                                <th>STATUS</th>
                                <th class="pe-4 text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.9rem;">
                            <?php 
                            if(mysqli_num_rows($q_stok) > 0) {
                            while($row = mysqli_fetch_assoc($q_stok)): 
                                // Logika Pintar Status Stok
                                $stok = $row['Jumlah_Stok'];
                                $kritis = $row['Batas_Kritis'];
                                
                                if($stok == 0) {
                                    $badge = '<span class="badge bg-secondary">Habis Total</span>';
                                } elseif($stok <= $kritis) {
                                    $badge = '<span class="badge bg-danger">Stok Kritis</span>';
                                } else {
                                    $badge = '<span class="badge bg-success">Aman</span>';
                                }
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= $row['Nama_Bahan'] ?><br><span class="text-muted fw-normal" style="font-size: 0.75rem;">ID: <?= $row['ID_BAHAN'] ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= $row['Kategori'] ?></span></td>
                                <td class="text-center fw-bold fs-6 <?= $stok <= $kritis ? 'text-danger' : 'text-primary' ?>"><?= $stok ?> <span class="fw-normal text-muted" style="font-size: 0.75rem;"><?= $row['Satuan'] ?></span></td>
                                <td class="text-center text-muted"><?= $kritis ?></td>
                                <td><?= $badge ?></td>
                                <td class="pe-4 text-end">
                                    <?php if($_SESSION['role'] != 'Kasir'): ?>
                                        <button class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['ID_BAHAN'] ?>"><i class="bi bi-pencil"></i></button>
                                        
                                        <a href="proses.php?hapus=<?= $row['ID_BAHAN'] ?>" class="btn btn-sm btn-light border text-danger btn-hapus" data-pesan="Item <?= $row['Nama_Bahan'] ?> akan dihapus. Hanya bisa dihapus jika belum pernah terpakai di resep pesanan!">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border"><i class="bi bi-eye"></i> Hanya Lihat</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?= $row['ID_BAHAN'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom-0"><h5 class="fw-bold mb-0">Edit Info Item</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <form action="proses.php" method="POST">
                                            <input type="hidden" name="id_bahan" value="<?= $row['ID_BAHAN'] ?>">
                                            <div class="modal-body py-0 row g-3">
                                                <div class="col-12">
                                                    <label class="form-label small">Nama Item</label>
                                                    <input type="text" name="nama_bahan" class="form-control" value="<?= $row['Nama_Bahan'] ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small">Kategori</label>
                                                    <input type="text" name="kategori" class="form-control" value="<?= $row['Kategori'] ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small">Batas Kritis Peringatan</label>
                                                    <input type="number" name="batas_kritis" class="form-control" value="<?= $row['Batas_Kritis'] ?>" required min="1">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top-0 mt-3"><button type="submit" name="edit_bahan" class="btn btn-primary w-100">Simpan Perubahan</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; 
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-4 text-muted">Data stok tidak ditemukan.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-mutasi" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">TANGGAL & WAKTU</th>
                                <th>JENIS MUTASI</th>
                                <th>NAMA ITEM</th>
                                <th class="text-center">JUMLAH</th>
                                <th class="pe-4">KETERANGAN / ALASAN</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.85rem;">
                            <?php 
                            if(mysqli_num_rows($q_mutasi) > 0) {
                                while($mutasi = mysqli_fetch_assoc($q_mutasi)): 
                                    $warna = $mutasi['Jenis'] == 'Masuk' ? 'text-success' : 'text-danger';
                                    $simbol = $mutasi['Jenis'] == 'Masuk' ? '+' : '-';
                            ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= date('d M Y, H:i', strtotime($mutasi['Tanggal'])) ?></td>
                                <td><span class="badge <?= $mutasi['Jenis'] == 'Masuk' ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= $warna ?> border"><?= $mutasi['Jenis'] ?></span></td>
                                <td class="fw-bold text-dark"><?= $mutasi['Nama_Bahan'] ?></td>
                                <td class="text-center fw-bold <?= $warna ?> fs-6"><?= $simbol ?><?= $mutasi['Jumlah'] ?> <span class="fw-normal text-muted" style="font-size: 0.75rem;"><?= $mutasi['Satuan'] ?></span></td>
                                <td class="pe-4 text-muted fst-italic"><?= $mutasi['Keterangan'] ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat mutasi manual.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahBaru" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0"><h5 class="fw-bold mb-0">Tambah Item Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="proses.php" method="POST">
                <div class="modal-body py-0 row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-medium">Kode ID (Otomatis / Kustom)</label>
                        <input type="text" name="id_bahan" class="form-control" value="ITM<?= rand(100,999) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Nama Item (Part/Medali Kosong)</label>
                        <input type="text" name="nama_bahan" class="form-control" placeholder="Contoh: Bahan Medali Kuningan" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Kategori</label>
                        <input type="text" name="kategori" class="form-control" placeholder="Contoh: Medali / Figure" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Satuan</label>
                        <input type="text" name="satuan" class="form-control" placeholder="Pcs / Set" value="Pcs" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Stok Awal</label>
                        <input type="number" name="stok_awal" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium text-danger">Batas Kritis</label>
                        <input type="number" name="batas_kritis" class="form-control" value="10" min="1" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 mt-3"><button type="submit" name="tambah_item" class="btn btn-primary w-100 fw-bold">Simpan Item</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMasuk" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0"><h5 class="fw-bold text-success mb-0"><i class="bi bi-box-arrow-in-down me-2"></i>Barang Masuk (Kulakan)</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="proses.php" method="POST">
                <input type="hidden" name="jenis_mutasi" value="Masuk">
                <div class="modal-body py-0 row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-medium">Pilih Item</label>
                        <select name="id_bahan" class="form-select" required>
                            <?php 
                            mysqli_data_seek($q_stok, 0);
                            while($opt = mysqli_fetch_assoc($q_stok)) { echo "<option value='{$opt['ID_BAHAN']}'>{$opt['Nama_Bahan']} (Sisa: {$opt['Jumlah_Stok']})</option>"; } 
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Jumlah Masuk</label>
                        <div class="input-group"><input type="number" name="jumlah" class="form-control border-success" required min="1"><span class="input-group-text bg-success text-white">Pcs/Set</span></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Keterangan / Supplier</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Kulakan dari Toko A..." required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 mt-3"><button type="submit" name="proses_mutasi" class="btn btn-success w-100 fw-bold">Proses Barang Masuk</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKeluar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0"><h5 class="fw-bold text-danger mb-0"><i class="bi bi-box-arrow-up me-2"></i>Barang Keluar (Manual)</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="proses.php" method="POST">
                <input type="hidden" name="jenis_mutasi" value="Keluar">
                <div class="modal-body py-0 row g-3">
                    <div class="col-12 text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Gunakan ini hanya untuk barang rusak/hilang. Barang yang terjual sudah otomatis terpotong di Kasir.</div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Pilih Item</label>
                        <select name="id_bahan" class="form-select" required>
                            <?php 
                            mysqli_data_seek($q_stok, 0);
                            while($opt = mysqli_fetch_assoc($q_stok)) { echo "<option value='{$opt['ID_BAHAN']}'>{$opt['Nama_Bahan']} (Sisa: {$opt['Jumlah_Stok']})</option>"; } 
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Jumlah Dikeluarkan</label>
                        <div class="input-group"><input type="number" name="jumlah" class="form-control border-danger" required min="1"><span class="input-group-text bg-danger text-white">Pcs/Set</span></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Alasan / Keterangan Wajib</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Marmer pecah saat dibor..." required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 mt-3"><button type="submit" name="proses_mutasi" class="btn btn-danger w-100 fw-bold">Keluarkan Barang</button></div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>